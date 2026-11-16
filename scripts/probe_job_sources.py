"""Small, unauthenticated HTML availability check; never uses private browser cookies."""
import concurrent.futures, json, re, tempfile, urllib.request, urllib.error
from pathlib import Path

SOURCES = {
    'jobstreet': 'https://my.jobstreet.com/software-jobs',
    'hiredly': 'https://my.hiredly.com/jobs?keyword=software&page=2',
    'ricebowl': 'https://www.ricebowl.my/jobsearch/software-engineer-jobs',
    'maukerja': 'https://www.maukerja.my/en/jobsearch/software-engineer-jobs',
    'jobstore': 'https://www.jobstore.com/my/jobs/q-software.html',
    'kerjait': 'https://www.kerja-it.com/',
}
DEST = Path(tempfile.gettempdir()) / 'career-job-source-probe'
DEST.mkdir(exist_ok=True)

def check(item):
    name, url = item
    try:
        request = urllib.request.Request(url, headers={'User-Agent':'SkillsCareer/1.0 (public job listing research)', 'Accept':'text/html'})
        with urllib.request.urlopen(request, timeout=20) as response:
            body = response.read(6000000).decode('utf-8', errors='replace')
            (DEST / (name+'.html')).write_text(body, encoding='utf-8')
            return {'source':name, 'status':response.status, 'url':response.url, 'bytes':len(body), 'scripts':re.findall(r'<script[^>]*(?:id|type)=["\']([^"\']+)',body)[:12], 'jobPosting':body.count('JobPosting'), 'challenge':bool(re.search('Just a moment|Verify you are human|Access Denied',body,re.I))}
    except Exception as error:
        return {'source':name,'error':str(error)}

if __name__ == '__main__':
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
        print(json.dumps(list(pool.map(check, SOURCES.items())), indent=2))
