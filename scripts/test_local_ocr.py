"""Use a synthetic image-only CV to check the installed local OCR, without user data."""
from pathlib import Path
import tempfile, subprocess, sys, json
from PIL import Image, ImageDraw, ImageFont
with tempfile.TemporaryDirectory(prefix='career-ocr-check-') as directory:
    image=Image.new('RGB',(1240,1754),'white'); draw=ImageDraw.Draw(image)
    font=ImageFont.truetype('C:/Windows/Fonts/arial.ttf',32)
    text='DEMO STUDENT\nSoftware Development Student\ndemo@example.test\n\nSKILLS\nLaravel, JavaScript, SQL\n\nPROJECTS\nCampus booking application\nBuilt accessible forms with Laravel and SQL.\n\nEDUCATION\nUniversiti Teknikal Malaysia Melaka\nComputer Science, 2024 - 2027'
    draw.multiline_text((80,90),text,font=font,fill='black',spacing=18)
    pdf=Path(directory)/'scanned-cv.pdf';image.save(pdf,'PDF',resolution=150)
    run=subprocess.run([sys.executable,str(Path(__file__).with_name('ocr_cv.py')),str(pdf)],capture_output=True,text=True,timeout=120)
    data=json.loads(run.stdout);assert run.returncode==0,data;assert 'Laravel' in data['text'] and 'SQL' in data['text'],data
    print(json.dumps({'passed':True,'ocrPages':data['ocrPages'],'characters':len(data['text'])}))
