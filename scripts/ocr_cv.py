"""Bounded local OCR. JSON on stdout; no document text is sent over a network."""
import contextlib
import json
import sys


def extract(path):
    import pypdfium2 as pdfium
    from rapidocr_onnxruntime import RapidOCR
    import numpy as np
    lines, scanned = [], 0
    with pdfium.PdfDocument(path) as document:
        if len(document) > 10:
            raise ValueError('Upload a CV with no more than 10 pages.')
        engine = None
        for page in document:
            try:
                textpage = page.get_textpage()
                try:
                    text = textpage.get_text_range().strip()
                finally:
                    textpage.close()
                if len(text) >= 50:
                    lines.append(text)
                    continue
                if engine is None:
                    engine = RapidOCR(intra_op_num_threads=2, inter_op_num_threads=1)
                width, height = page.get_size()
                if min(width, height) <= 0:
                    raise ValueError('This PDF contains an invalid page.')
                scale = min(2.3, 2400 / max(width, height))
                bitmap = page.render(scale=scale)
                try:
                    image = bitmap.to_pil().convert('RGB')
                    results, _ = engine(np.asarray(image))
                    lines.append('\n'.join(item[1] for item in (results or []) if item[2] >= 0.45))
                    image.close()
                finally:
                    bitmap.close()
                scanned += 1
                if sum(map(len, lines)) > 20000:
                    raise ValueError('This document is too long. Use a CV with at most 20,000 characters.')
            finally:
                page.close()
    return {'text': '\n\n'.join(lines), 'ocrPages': scanned, 'method': 'rapidocr-1.4.4'}


if __name__ == '__main__':
    try:
        with contextlib.redirect_stdout(sys.stderr):
            result = extract(sys.argv[1])
        print(json.dumps(result, ensure_ascii=True))
    except Exception as error:
        message = str(error) if isinstance(error, ValueError) else 'The scanned PDF could not be read. Upload an unlocked PDF or paste your CV text.'
        print(json.dumps({'error': message}))
        sys.exit(1)
