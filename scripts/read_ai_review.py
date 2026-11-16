import json,sys
from pathlib import Path
report=json.loads(Path(sys.argv[1]).read_text(encoding='utf-8'))
for i,c in enumerate(report['cases']):
    if i<int(sys.argv[2] if len(sys.argv)>2 else 6):continue
    print(str(i+1)+'. '+c['question']+' ['+str(c.get('seconds','followup'))+'s]')
    print(c['answer'])
    print('Actions: '+str([a['path'] for a in c['actions']]))
