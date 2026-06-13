from pathlib import Path
from textwrap import wrap

from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas

root = Path(__file__).resolve().parent
src = root / "GUIDE_DISTRIBUTION_CLIENT_DESKTOP.md"
out = root / "GUIDE_DISTRIBUTION_CLIENT_DESKTOP.pdf"

lines = src.read_text(encoding="utf-8").splitlines()

page_width, page_height = A4
left_margin = 56
right_margin = 56
top_margin = 64
bottom_margin = 56
line_height = 14
max_chars = 100

c = canvas.Canvas(str(out), pagesize=A4)
c.setTitle("Guide Distribution Client Desktop")
c.setAuthor("Equipe IT PLUSSCI")


def new_page() -> float:
    c.setFont("Helvetica", 10)
    return page_height - top_margin


y = new_page()

for raw in lines:
    line = raw.rstrip()

    if line.startswith("# "):
        chunks = wrap(line[2:], width=70) or [""]
        c.setFont("Helvetica-Bold", 16)
        for chunk in chunks:
            if y < bottom_margin:
                c.showPage()
                y = new_page()
                c.setFont("Helvetica-Bold", 16)
            c.drawString(left_margin, y, chunk)
            y -= 18
        y -= 4
        continue

    if line.startswith("## "):
        chunks = wrap(line[3:], width=78) or [""]
        c.setFont("Helvetica-Bold", 13)
        for chunk in chunks:
            if y < bottom_margin:
                c.showPage()
                y = new_page()
                c.setFont("Helvetica-Bold", 13)
            c.drawString(left_margin, y, chunk)
            y -= 16
        y -= 2
        continue

    if line.startswith("### "):
        chunks = wrap(line[4:], width=84) or [""]
        c.setFont("Helvetica-Bold", 11)
        for chunk in chunks:
            if y < bottom_margin:
                c.showPage()
                y = new_page()
                c.setFont("Helvetica-Bold", 11)
            c.drawString(left_margin, y, chunk)
            y -= 15
        continue

    if line.strip() == "":
        y -= line_height
        if y < bottom_margin:
            c.showPage()
            y = new_page()
        continue

    if line.startswith("- "):
        line = "* " + line[2:]

    c.setFont("Helvetica", 10)
    chunks = wrap(line, width=max_chars) or [""]
    for chunk in chunks:
        if y < bottom_margin:
            c.showPage()
            y = new_page()
            c.setFont("Helvetica", 10)
        c.drawString(left_margin, y, chunk)
        y -= line_height

c.save()
print(out)
