#!/usr/bin/env python3
"""
Convert DOCUMENTATION.md to a print-friendly HTML file.
Open the generated HTML in a browser and use Print (Ctrl+P) → Save as PDF.
"""
import re
import sys
from pathlib import Path

def md_to_html(md: str) -> str:
    lines = md.split('\n')
    out = []
    i = 0
    in_table = False
    table_rows = []

    def flush_table():
        nonlocal table_rows, out
        if not table_rows:
            return
        out.append('<table>')
        for idx, row in enumerate(table_rows):
            cells = [c.strip() for c in row.split('|')]
            cells = [c for c in cells if c]
            if idx == 1 and cells and all(re.match(r'^[-:\s]+$', c) for c in cells):
                continue  # skip separator row
            tag = 'th' if idx == 0 else 'td'
            out.append('<tr>')
            for c in cells:
                out.append(f'<{tag}>{html_escape(c)}</{tag}>')
            out.append('</tr>')
        out.append('</table>')
        table_rows = []

    def html_escape(s: str) -> str:
        return s.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;')

    while i < len(lines):
        line = lines[i]
        orig = line

        # Table detection
        if line.strip().startswith('|') and '|' in line:
            if not in_table:
                flush_table()
                in_table = True
            table_rows.append(line)
            i += 1
            continue
        else:
            if in_table:
                flush_table()
                in_table = False

        # Headers
        if line.startswith('# '):
            out.append(f'<h1>{html_escape(line[2:].strip())}</h1>')
            i += 1
            continue
        if line.startswith('## '):
            out.append(f'<h2>{html_escape(line[3:].strip())}</h2>')
            i += 1
            continue
        if line.startswith('### '):
            out.append(f'<h3>{html_escape(line[4:].strip())}</h3>')
            i += 1
            continue
        if line.startswith('#### '):
            out.append(f'<h4>{html_escape(line[5:].strip())}</h4>')
            i += 1
            continue

        # Horizontal rule
        if line.strip() in ('---', '***', '___'):
            out.append('<hr>')
            i += 1
            continue

        # Code block
        if line.strip().startswith('```'):
            out.append('<pre><code>')
            i += 1
            while i < len(lines) and not lines[i].strip().startswith('```'):
                out.append(html_escape(lines[i]) + '\n')
                i += 1
            if i < len(lines):
                i += 1
            out.append('</code></pre>')
            continue

        # Empty line
        if not line.strip():
            out.append('<p>')
            i += 1
            continue

        # List item
        if re.match(r'^[\s]*[-*]\s+', line) or re.match(r'^[\s]*\d+\.\s+', line):
            if out and out[-1] != '<ul>' and out[-1] != '<ol>':
                out.append('<ul>' if line.strip().startswith(('-', '*')) else '<ol>')
            content = re.sub(r'^[\s]*[-*]\s+', '', line)
            content = re.sub(r'^[\s]*\d+\.\s+', '', content)
            # Bold
            content = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', content)
            out.append(f'<li>{content}</li>')
            i += 1
            continue

        # Paragraph with inline bold and code
        content = html_escape(line)
        content = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', content)
        content = re.sub(r'`([^`]+)`', r'<code>\1</code>', content)
        out.append(f'<p>{content}</p>')
        i += 1

    flush_table()

    # Close list if open
    html = '\n'.join(out)
    html = re.sub(r'(<li>[^<]+</li>)(\s*)(?=<h[12]|$)', r'\1</ul>\2', html)
    return html

def main():
    root = Path(__file__).resolve().parent.parent
    md_path = root / 'DOCUMENTATION.md'
    html_path = root / 'DOCUMENTATION.html'

    if not md_path.exists():
        print(f'Not found: {md_path}', file=sys.stderr)
        sys.exit(1)

    md = md_path.read_text(encoding='utf-8')
    body = md_to_html(md)

    html_doc = f'''<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>EcoSpot — Documentation technique et suivi PIDEV</title>
    <style>
        body {{ font-family: Georgia, serif; max-width: 800px; margin: 2em auto; padding: 0 1em; line-height: 1.5; color: #222; }}
        h1 {{ font-size: 1.6em; border-bottom: 2px solid #333; padding-bottom: 0.2em; }}
        h2 {{ font-size: 1.3em; margin-top: 1.2em; }}
        h3 {{ font-size: 1.1em; margin-top: 1em; }}
        table {{ border-collapse: collapse; width: 100%; margin: 0.8em 0; font-size: 0.95em; }}
        th, td {{ border: 1px solid #ccc; padding: 6px 10px; text-align: left; }}
        th {{ background: #f0f0f0; }}
        pre {{ background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 4px; }}
        code {{ font-family: Consolas, monospace; font-size: 0.9em; }}
        p {{ margin: 0.5em 0; }}
        ul, ol {{ margin: 0.5em 0; padding-left: 1.5em; }}
        hr {{ margin: 1.5em 0; border: none; border-top: 1px solid #ccc; }}
        @media print {{ body {{ max-width: 100%; }} }}
    </style>
</head>
<body>
{body}
</body>
</html>
'''
    html_path.write_text(html_doc, encoding='utf-8')
    print(f'Generated: {html_path}')
    print('Open it in a browser and use Print (Ctrl+P) → Save as PDF.')

if __name__ == '__main__':
    main()
