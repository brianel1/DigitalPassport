"""Encode labelled, real application capture clips. Requires FFmpeg on PATH."""
from pathlib import Path
import subprocess, shutil, tempfile, json
root=Path(__file__).resolve().parents[1]
source=Path(tempfile.gettempdir())/'career-product-recording'
out=root/'frontend/public/media'
ffmpeg=shutil.which('ffmpeg');assert ffmpeg,'FFmpeg is required'
segments=[]
for i in range(1,5):
    frames=sorted(source.glob(f'{i}-*.png'));assert frames,f'Missing clip {i}'
    target=source/f'clip-{i}.mp4'
    subprocess.run([ffmpeg,'-y','-v','error','-framerate','8','-i',str(source/f'{i}-%04d.png'),'-vf','fps=24,scale=1280:1000','-c:v','libx264','-preset','medium','-crf','22','-pix_fmt','yuv420p','-an',str(target)],check=True)
    segments.append("file '"+target.as_posix()+"'")
manifest=source/'concat.txt';manifest.write_text('\n'.join(segments),encoding='utf-8')
master=out/'product-walkthrough.mp4'
subprocess.run([ffmpeg,'-y','-v','error','-f','concat','-safe','0','-i',str(manifest),'-c','copy','-movflags','+faststart',str(master)],check=True)
subprocess.run([ffmpeg,'-y','-v','error','-ss','1','-i',str(master),'-frames:v','1','-c:v','libwebp','-quality','85',str(out/'product-walkthrough-poster.webp')],check=True)
for suffix,width in [('',1280),('-small',768)]:
    if suffix:
        subprocess.run([ffmpeg,'-y','-v','error','-i',str(master),'-vf',f'scale={width}:-2','-c:v','libx264','-crf','25','-an','-movflags','+faststart',str(out/f'product-walkthrough{suffix}.mp4')],check=True)
    subprocess.run([ffmpeg,'-y','-v','error','-i',str(master),'-vf',f'scale={width}:-2','-c:v','libvpx-vp9','-b:v','0','-crf','35','-row-mt','1','-an',str(out/f'product-walkthrough{suffix}.webm')],check=True)
print(json.dumps({'files':{p.name:p.stat().st_size for p in out.glob('product-walkthrough*')},'result':json.loads((source/'measured-result.json').read_text())}))
