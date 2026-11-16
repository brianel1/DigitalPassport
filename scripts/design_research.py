"""Run the installed design skill with Windows-safe argument handling."""
import subprocess
import sys
from pathlib import Path

skill = Path.home() / '.codex' / 'skills' / 'ui-ux-pro-max' / 'scripts' / 'search.py'
queries = {
    'system': ['education skills platform immersive', '--design-system', '--variance', '7', '--motion', '6', '--density', '4', '-p', 'Student Skills and Career'],
    'typography': ['geometric modern sans', '--domain', 'typography', '-n', '2'],
    'vue': ['cleanup lifecycle', '--stack', 'vue', '-n', '3'],
    'style': ['dark mode oled', '--domain', 'style', '-n', '1'],
}
subprocess.run([sys.executable, str(skill), *queries[sys.argv[1]]], check=True)
