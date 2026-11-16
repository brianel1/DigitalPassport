"""Render original product motion graphics. Pillow + FFmpeg; no external footage.

Run from the project root: python scripts\render_product_video.py
All frames are original vector-like shapes, typography and workflow diagrams.
"""
from pathlib import Path
import math
import shutil
import subprocess
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'frontend' / 'public' / 'media'
OUT.mkdir(parents=True, exist_ok=True)
W, H, FPS, DURATION = 1280, 960, 24, 20
BG, CARD, LINE = '#142b45', '#1c3854', '#3a5672'
WHITE, MUTED, BLUE, TEAL = '#f3f6fc', '#b5c7dc', '#b8caff', '#8cdbca'
FONTS = Path('C:/Windows/Fonts')
cache = {}

def font(size, bold=False):
    key = (size, bold)
    if key not in cache:
        cache[key] = ImageFont.truetype(str(FONTS / ('seguisb.ttf' if bold else 'segoeui.ttf')), size)
    return cache[key]

def ease(t):
    t = max(0, min(1, t))
    return 1 - (1-t)**3

def box(d, rect, fill=CARD, outline=LINE, radius=22):
    d.rounded_rectangle(tuple(round(x) for x in rect), radius=radius, fill=fill, outline=outline, width=2)

def text(d, xy, label, size=28, color=WHITE, bold=False):
    d.text(xy, label, font=font(size, bold), fill=color)

def check(d, x, y, color=TEAL, scale=1):
    d.line([(x,y+10*scale), (x+8*scale,y+18*scale), (x+23*scale,y)], fill=color, width=max(2, round(4*scale)))

def doc(d, x, y, color=BLUE):
    d.rounded_rectangle((x,y,x+42,y+52), radius=6, outline=color, width=3)
    for offset, length in [(16,22),(26,22),(36,15)]:
        d.line((x+10,y+offset,x+10+length,y+offset), fill=color, width=2)

def arrow(d, x, y, progress):
    end = x + 130 * ease(progress)
    d.line((x,y,end,y), fill=TEAL, width=3)
    if progress > .8:
        d.line([(end-10,y-8),(end,y),(end-10,y+8)], fill=TEAL, width=3)

def shell(t):
    im = Image.new('RGB', (W,H), BG)
    d = ImageDraw.Draw(im)
    # Solid blocks and architectural lines. Deliberately no gradients.
    for x,y in [(52,52),(1180,52),(52,856),(1180,856)]:
        d.line((x,y,x+26,y), fill=LINE, width=2)
        d.line((x,y,x,y+26), fill=LINE, width=2)
    text(d, (86,75), 'YOUR SKILLS, CONNECTED.', 20, MUTED, True)
    text(d, (86,117), ['Give your work a place.', 'Turn evidence into trust.', 'See where your skills fit.', 'Prepare your next move.'][int(t//5)%4], 45, WHITE, True)
    text(d, (86,824), 'EVIDENCE', 18, BLUE, True)
    text(d, (391,824), 'VERIFICATION', 18, BLUE, True)
    text(d, (733,824), 'OPPORTUNITIES', 18, BLUE, True)
    text(d, (1118,824), 'CV', 18, BLUE, True)
    d.line((86,884,1192,884), fill=LINE, width=3)
    d.line((86,884,86+1106*(t/DURATION),884), fill=TEAL, width=4)
    for i in range(4):
        x=86+i*368.6
        d.ellipse((x-5,879,x+5,889), fill=TEAL if t>=i*5 else LINE)
    return im

def scene(d, phase, local):
    p = ease(local/.8)
    shift = (1-p)*35
    if phase == 0:
        for i, (label, sub, accent) in enumerate([('Projects','The work you have built',BLUE),('Certifications','What you have learned',TEAL),('Skills','What you can put into practice',BLUE)]):
            y=244+i*157+max(0,1-ease((local-i*.18)/.7))*45
            box(d, (88,y,638,y+128))
            doc(d,118,y+35,accent)
            text(d,(183,y+27),label,29,WHITE,True)
            text(d,(183,y+70),sub,22,MUTED)
        arrow(d,660,476,local-1)
        box(d,(812,285+shift,1190,673+shift),'#edf3fc','#edf3fc',26)
        text(d,(847,324+shift),'YOUR RECORD',18,'#3157d5',True)
        text(d,(847,374+shift),'A fuller picture',31,'#10243a',True)
        text(d,(847,420+shift),'of your skills.',31,'#10243a',True)
        for i, label in enumerate(['Skills & evidence','Assessment results','Career preferences']):
            y=500+i*45+shift
            d.ellipse((848,y+6,856,y+14),fill='#0f766e')
            text(d,(873,y),label,21,'#52657a')
    elif phase == 1:
        box(d,(88,247+shift,598,720+shift))
        text(d,(124,284+shift),'SUPPORTING EVIDENCE',19,TEAL,True)
        doc(d,125,349+shift)
        text(d,(190,353+shift),'Project or certification',27,WHITE,True)
        for i,label in enumerate(['Linked to a skill','Ready for review','Reviewed by an administrator']):
            y=447+i*65+shift
            d.ellipse((125,y,153,y+28),outline=LINE,width=2)
            if local > .7+i*.55: check(d,130,y+7,TEAL,.65)
            text(d,(173,y-2),label,23,MUTED)
        arrow(d,625,480,local-1.9)
        box(d,(788,300,1190,664),'#e3f2ee','#e3f2ee',26)
        d.ellipse((942,340,1038,436),fill='#0f766e')
        if local > 2: check(d,966,369,'#ffffff',1.8)
        text(d,(828,466),'Verified skills',35,'#10243a',True)
        text(d,(828,523),'Backed by reviewed work.',23,'#52657a')
        text(d,(828,560),'Ready for your profile.',23,'#52657a')
    elif phase == 2:
        box(d,(88,260+shift,530,709+shift))
        text(d,(122,299+shift),'YOUR VERIFIED SKILLS',19,TEAL,True)
        for i,label in enumerate(['Skills you can prove','Projects you have built','Your career preferences']):
            y=371+i*87+shift
            box(d,(120,y,496,y+62),'#284866',LINE,12)
            text(d,(143,y+15),label,23,WHITE)
        arrow(d,553,480,local-.6)
        box(d,(713,260,1190,709),'#edf3fc','#edf3fc',26)
        text(d,(750,299),'EXPLORE OPPORTUNITIES',19,'#3157d5',True)
        text(d,(750,352),'Understand your fit.',32,'#10243a',True)
        for i,(label,sub) in enumerate([('Matched skills','See your strengths'),('Skills to develop','Find your next learning goal'),('Original job listing','Review the role at its source')]):
            y=423+i*80
            d.line((750,y-12,1153,y-12),fill='#cdd9e6',width=2)
            text(d,(750,y),label,24,'#0f766e',True)
            text(d,(750,y+32),sub,21,'#52657a')
    else:
        box(d,(88,272+shift,566,698+shift))
        text(d,(124,310+shift),'YOUR NEXT APPLICATION',19,TEAL,True)
        text(d,(124,368+shift),'Make it relevant.',35,WHITE,True)
        for i,label in enumerate(['Start with your verified record','Tailor the content to a role','Review and edit every section']):
            y=452+i*63+shift
            check(d,125,y+5,TEAL,.8)
            text(d,(164,y),label,23,MUTED)
        arrow(d,590,480,local-.8)
        # A clean document built from shapes; no invented personal data.
        box(d,(752,237,1148,748),'#ffffff','#ffffff',15)
        text(d,(792,272),'YOUR CV',34,'#10243a',True)
        text(d,(792,320),'Your experience, clearly presented.',19,'#52657a')
        for i,(label,lengths) in enumerate([('PROFILE',[275,240]),('SKILLS',[220]),('EXPERIENCE',[270,250])]):
            y=383+i*96
            text(d,(792,y),label,17,'#3157d5',True)
            for j,length in enumerate(lengths):
                d.rounded_rectangle((792,y+36+j*16,792+length*ease((local-.5-i*.35)/.8),y+41+j*16),radius=2,fill='#dce4ef')
        box(d,(792,675,1108,718),'#e3f2ee','#e3f2ee',9)
        text(d,(864,681),'PDF  /  WORD',19,'#0f766e',True)

def frame(t):
    phase=int(t//5)%4
    local=t%5
    base=shell(t)
    layer=Image.new('RGBA',(W,H),(0,0,0,0))
    scene(ImageDraw.Draw(layer),phase,local)
    # Opacity transitions, with only flat fills within each element.
    alpha=min(1,local/.32,(5-local)/.32)
    layer.putalpha(layer.getchannel('A').point(lambda a: round(a*alpha)))
    base.paste(layer,(0,0),layer)
    return base

def run():
    ffmpeg=shutil.which('ffmpeg')
    if not ffmpeg: raise SystemExit('FFmpeg is required on PATH.')
    frame(2.8).save(OUT/'skills-journey-poster.webp',quality=90)
    # Master MP4; streamed frames avoid hundreds of temporary images.
    command=[ffmpeg,'-y','-v','error','-f','rawvideo','-pix_fmt','rgb24','-s',f'{W}x{H}','-r',str(FPS),'-i','-','-an','-c:v','libx264','-preset','medium','-crf','23','-pix_fmt','yuv420p','-movflags','+faststart',str(OUT/'skills-journey.mp4')]
    proc=subprocess.Popen(command,stdin=subprocess.PIPE)
    try:
        for i in range(FPS*DURATION):
            proc.stdin.write(frame(i/FPS).tobytes())
            if i % (FPS*5)==0: print(f'Rendering scene {i//(FPS*5)+1}/4',flush=True)
    finally: proc.stdin.close()
    if proc.wait(): raise RuntimeError('Master render failed')
    for suffix,scale in [('',1280),('-small',640)]:
        source=OUT/'skills-journey.mp4'
        if suffix:
            subprocess.run([ffmpeg,'-y','-v','error','-i',str(source),'-vf',f'scale={scale}:-2','-c:v','libx264','-crf','25','-an','-movflags','+faststart',str(OUT/f'skills-journey{suffix}.mp4')],check=True)
        subprocess.run([ffmpeg,'-y','-v','error','-i',str(source),'-vf',f'scale={scale}:-2','-c:v','libvpx-vp9','-b:v','0','-crf','36','-row-mt','1','-an',str(OUT/f'skills-journey{suffix}.webm')],check=True)
    for file in sorted(OUT.glob('skills-journey*')):
        print(f'{file.name}: {file.stat().st_size/1024:.0f} KB',flush=True)

if __name__=='__main__': run()
