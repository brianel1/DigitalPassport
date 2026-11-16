"""Exercise the real upload endpoint with an image-only PDF using an isolated QA account."""
from pathlib import Path
from PIL import Image,ImageDraw,ImageFont
import json,sys,urllib.request,tempfile,uuid,time
session=json.loads(Path(sys.argv[1]).read_text())
assert session['cleanup']['email'].startswith('ui-review-')
headers={'Authorization':'Bearer '+session['student']['token'],'Accept':'application/json'}
with tempfile.TemporaryDirectory(prefix='ocr-http-') as directory:
    im=Image.new('RGB',(1240,1754),'white');d=ImageDraw.Draw(im);font=ImageFont.truetype('C:/Windows/Fonts/arial.ttf',32)
    d.multiline_text((80,90),'DEMO STUDENT\nSoftware Development Student\ndemo@example.test\n\nSKILLS\nLaravel, JavaScript, SQL\n\nPROJECTS\nCampus booking application\nBuilt accessible forms with Laravel and SQL.',font=font,fill='black',spacing=18)
    pdf=Path(directory)/'scanned.pdf';im.save(pdf,'PDF',resolution=150)
    boundary=uuid.uuid4().hex
    payload=(f'--{boundary}\r\nContent-Disposition: form-data; name="title"\r\n\r\nScanned CV verification\r\n--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="scanned.pdf"\r\nContent-Type: application/pdf\r\n\r\n'.encode()+pdf.read_bytes()+f'\r\n--{boundary}--\r\n'.encode())
    start=time.monotonic();request=urllib.request.Request('http://localhost:5173/api/student/cvs/upload',data=payload,headers={**headers,'Content-Type':'multipart/form-data; boundary='+boundary})
    with urllib.request.urlopen(request,timeout=150) as response:data=json.load(response)
    try:
        assert data['extractionMethod']=='local_ocr',data
        assert 'Laravel' in data['sourceText'] and data['title']=='Scanned CV verification'
        print(json.dumps({'passed':True,'method':data['extractionMethod'],'seconds':round(time.monotonic()-start,2),'characters':len(data['sourceText'])}))
    finally:
        urllib.request.urlopen(urllib.request.Request(f"http://localhost:5173/api/student/cvs/{data['id']}",method='DELETE',headers=headers)).close()
