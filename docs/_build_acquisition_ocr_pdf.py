"""
Build ACQUISITION_OCR.pdf from ACQUISITION_OCR.md
Uses reportlab (same stack as other project PDF builders).

Features:
  - Cover page (title, subtitle, date, author)
  - Page numbers in the footer (Page X / Y) — skipped on the cover
  - PDF bookmarks (outline) for H1 and H2 headings
  - Tables, code blocks, bullet/numbered lists
  - Flow-diagram section rendered as a styled text box
"""

import re
from datetime import date
from pathlib import Path
from textwrap import wrap

from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.pdfgen.canvas import Canvas as CanvasBase

# ---------------------------------------------------------------------------
# Paths
# ---------------------------------------------------------------------------
root = Path(__file__).resolve().parent
src = root / "ACQUISITION_OCR.md"
out = root / "ACQUISITION_OCR.pdf"

# ---------------------------------------------------------------------------
# Page geometry
# ---------------------------------------------------------------------------
PAGE_W, PAGE_H = A4
L_MARGIN      = 56
R_MARGIN      = 56
T_MARGIN      = 64
B_MARGIN      = 64          # increased to leave room for footer
LINE_H        = 14
USABLE_W      = PAGE_W - L_MARGIN - R_MARGIN     # ≈ 483 pt
MAX_CHARS     = 98                                 # body wrap width (chars)

COVER_PAGE = 1   # the cover is always physical page 1


# ---------------------------------------------------------------------------
# NumberedCanvas — adds header/footer + tracks total page count
# ---------------------------------------------------------------------------

class NumberedCanvas(CanvasBase):
    """Two-pass canvas: saves all page states, then stamps footers on save()."""

    def __init__(self, *args, **kwargs):
        CanvasBase.__init__(self, *args, **kwargs)
        self._saved_page_states: list[dict] = []

    def showPage(self) -> None:            # type: ignore[override]
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self) -> None:               # type: ignore[override]
        total = len(self._saved_page_states)
        for physical_num, state in enumerate(self._saved_page_states, 1):
            self.__dict__.update(state)
            self._draw_footer(physical_num, total)
            CanvasBase.showPage(self)
        CanvasBase.save(self)

    def _draw_footer(self, physical_num: int, total: int) -> None:
        """Stamp page footer on every page except the cover."""
        if physical_num == COVER_PAGE:
            return
        content_page = physical_num - COVER_PAGE       # 1-based content page
        content_total = total - COVER_PAGE
        self.saveState()
        # Separator line
        self.setStrokeColor(colors.HexColor("#CBD5E1"))
        self.setLineWidth(0.5)
        self.line(L_MARGIN, B_MARGIN - 16, PAGE_W - R_MARGIN, B_MARGIN - 16)
        # Left label
        self.setFont("Helvetica", 7.5)
        self.setFillColor(colors.HexColor("#94A3B8"))
        self.drawString(L_MARGIN, B_MARGIN - 28,
                        "Acquisition & OCR — Documentation technique — PLUSSCI")
        # Right page number
        self.drawRightString(
            PAGE_W - R_MARGIN, B_MARGIN - 28,
            f"Page {content_page} / {content_total}"
        )
        self.restoreState()

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def strip_inline(text: str) -> str:
    """Remove **bold**, *italic*, `code` backtick markers and []() links."""
    text = re.sub(r'\*\*(.+?)\*\*', r'\1', text)
    text = re.sub(r'\*(.+?)\*',     r'\1', text)
    text = re.sub(r'`(.+?)`',       r'\1', text)
    text = re.sub(r'\[([^\]]+)\]\([^)]*\)', r'\1', text)
    return text


def safe(text: str) -> str:
    """Replace characters that Latin-1 cannot encode with ASCII equivalents."""
    replacements = {
        '\u2019': "'", '\u2018': "'", '\u201c': '"', '\u201d': '"',
        '\u2013': '-', '\u2014': '-', '\u2022': '*', '\u00b7': '*',
        '\u2026': '...', '\u00a0': ' ', '\u2192': '->', '\u2190': '<-',
        '\u25b6': '>', '\u25bc': 'v', '\u2713': 'v', '\u2714': 'v',
        '\u2717': 'x', '\u2718': 'x', '\u00e9': 'e', '\u00e8': 'e',
        '\u00ea': 'e', '\u00eb': 'e', '\u00e0': 'a', '\u00e2': 'a',
        '\u00e4': 'a', '\u00ee': 'i', '\u00ef': 'i', '\u00f4': 'o',
        '\u00f6': 'o', '\u00f9': 'u', '\u00fb': 'u', '\u00fc': 'u',
        '\u00e7': 'c', '\u00c9': 'E', '\u00c8': 'E', '\u00ca': 'E',
        '\u00c0': 'A', '\u00c2': 'A', '\u00ce': 'I', '\u00d4': 'O',
        '\u00d9': 'U', '\u00db': 'U', '\u00c7': 'C', '\u00b0': 'deg',
        '\u00ab': '<<', '\u00bb': '>>', '\u00b2': '2', '\u00b3': '3',
        '\u2248': '~', '\u00d7': 'x', '\u00f7': '/',
    }
    out_chars = []
    for ch in text:
        out_chars.append(replacements.get(ch, ch if ord(ch) < 256 else '?'))
    return ''.join(out_chars)


# ---------------------------------------------------------------------------
# Canvas state
# ---------------------------------------------------------------------------
c = NumberedCanvas(str(out), pagesize=A4)
c.setTitle("Acquisition & OCR - Documentation technique")
c.setAuthor("Equipe IT PLUSSCI")


# ---------------------------------------------------------------------------
# Cover page
# ---------------------------------------------------------------------------

def draw_cover() -> None:
    """Draw a styled cover page."""
    # Top colour band
    c.setFillColor(colors.HexColor("#0EA5E9"))
    c.rect(0, PAGE_H - 120, PAGE_W, 120, fill=1, stroke=0)

    # Title (white, large)
    c.setFont("Helvetica-Bold", 26)
    c.setFillColor(colors.white)
    c.drawCentredString(PAGE_W / 2, PAGE_H - 68, "Acquisition & OCR")

    # Subtitle
    c.setFont("Helvetica", 13)
    c.drawCentredString(PAGE_W / 2, PAGE_H - 92, "Documentation technique — Module GED PLUSSCI")

    # Body area
    mid_y = PAGE_H / 2 + 20

    # Decorative horizontal bar
    c.setFillColor(colors.HexColor("#E0F2FE"))
    c.rect(L_MARGIN, mid_y - 4, USABLE_W, 2, fill=1, stroke=0)

    # Description block
    c.setFont("Helvetica", 11)
    c.setFillColor(colors.HexColor("#334155"))
    description_lines = [
        "Ce document decrit l'architecture et le fonctionnement du module",
        "d'acquisition et d'OCR : canaux d'import (upload, scan, e-mail),",
        "pipeline de traitement du texte, classification automatique,",
        "formats supportes et configuration operationnelle.",
    ]
    dy = mid_y - 30
    for line in description_lines:
        c.drawCentredString(PAGE_W / 2, dy, line)
        dy -= 18

    # Bottom metadata band
    c.setFillColor(colors.HexColor("#F1F5F9"))
    c.rect(0, 0, PAGE_W, 72, fill=1, stroke=0)
    c.setFont("Helvetica", 9)
    c.setFillColor(colors.HexColor("#64748B"))
    c.drawString(L_MARGIN, 48, f"Equipe IT PLUSSCI")
    c.drawString(L_MARGIN, 32, f"Date : {date.today().strftime('%d/%m/%Y')}")
    c.drawRightString(PAGE_W - R_MARGIN, 48, "Confidentiel — Usage interne")
    c.drawRightString(PAGE_W - R_MARGIN, 32, "Version 1.1")

    c.showPage()


draw_cover()


def new_page() -> float:
    c.setFont("Helvetica", 10)
    return PAGE_H - T_MARGIN


y = new_page()


def ensure_space(needed: float) -> float:
    global y
    if y - needed < B_MARGIN:
        c.showPage()
        y = new_page()
    return y


def draw_line(text: str, font: str, size: float, indent: float = 0,
              extra_before: float = 0, extra_after: float = 0,
              wrap_width: int = MAX_CHARS, color=colors.black) -> None:
    global y
    y -= extra_before
    c.setFont(font, size)
    c.setFillColor(color)
    for chunk in (wrap(text, wrap_width) if text.strip() else [""]):
        ensure_space(LINE_H + 2)
        c.drawString(L_MARGIN + indent, y, chunk)
        y -= LINE_H
    y -= extra_after
    c.setFillColor(colors.black)


# ---------------------------------------------------------------------------
# Table renderer
# ---------------------------------------------------------------------------

def render_table(rows: list[str]) -> None:
    """Render Markdown pipe-table rows."""
    global y

    # Parse cells
    parsed = []
    for row in rows:
        cells = [strip_inline(c.strip()) for c in row.strip().strip('|').split('|')]
        parsed.append(cells)

    if not parsed:
        return

    # Filter separator rows (---|--- …)
    data = [r for r in parsed if not all(re.match(r'^[-:]+$', cell) for cell in r)]
    if not data:
        return

    n_cols = max(len(r) for r in data)
    # Normalize column count
    data = [r + [''] * (n_cols - len(r)) for r in data]

    # Column widths: distribute proportionally, cap at USABLE_W
    col_w = USABLE_W / n_cols
    row_h = LINE_H + 2
    padding = 4

    c.setFont("Helvetica", 9)

    for row_idx, row in enumerate(data):
        is_header = row_idx == 0
        font_name = "Helvetica-Bold" if is_header else "Helvetica"
        bg_color  = colors.HexColor("#F1F5F9") if is_header else colors.white

        # Compute row height (multi-line cells)
        max_lines = 1
        wrapped_cells = []
        for cell in row:
            cell_text = safe(cell)
            max_cell_chars = max(int(col_w / 5.2), 10)
            lines = wrap(cell_text, max_cell_chars) or ['']
            wrapped_cells.append(lines)
            max_lines = max(max_lines, len(lines))

        total_h = max_lines * row_h + padding * 2
        ensure_space(total_h + 2)

        # Draw background
        c.setFillColor(bg_color)
        c.rect(L_MARGIN, y - total_h, USABLE_W, total_h, fill=1, stroke=0)
        c.setFillColor(colors.HexColor("#CBD5E1"))
        c.rect(L_MARGIN, y - total_h, USABLE_W, total_h, fill=0, stroke=1)
        c.setFillColor(colors.black)

        # Draw cell text
        for col_idx, cell_lines in enumerate(wrapped_cells):
            x = L_MARGIN + col_idx * col_w + padding
            cell_y = y - padding - row_h + 2
            c.setFont(font_name, 9)
            for line in cell_lines:
                c.drawString(x, cell_y, safe(line))
                cell_y -= row_h

        y -= total_h


# ---------------------------------------------------------------------------
# Code block renderer
# ---------------------------------------------------------------------------

def render_code_block(lines: list[str]) -> None:
    global y
    if not lines:
        return

    line_h = 12
    padding = 4
    n = len(lines)
    block_h = n * line_h + padding * 2

    # Try to keep on one page; if too tall, split
    if y - block_h < B_MARGIN:
        c.showPage()
        y = new_page()

    # Background box (best-effort single page)
    draw_h = min(block_h, y - B_MARGIN)
    c.setFillColor(colors.HexColor("#F8FAFC"))
    c.rect(L_MARGIN, y - draw_h, USABLE_W, draw_h, fill=1, stroke=0)
    c.setFillColor(colors.HexColor("#CBD5E1"))
    c.rect(L_MARGIN, y - draw_h, USABLE_W, draw_h, fill=0, stroke=1)
    c.setFillColor(colors.black)

    c.setFont("Courier", 8)
    cy = y - padding - line_h + 2
    for code_line in lines:
        if cy < B_MARGIN:
            c.showPage()
            y = new_page()
            cy = y - padding - line_h + 2
        text = safe(code_line.rstrip())[:110]
        c.drawString(L_MARGIN + 6, cy, text)
        cy -= line_h

    y = cy - padding


# ---------------------------------------------------------------------------
# Main parsing loop
# ---------------------------------------------------------------------------

all_lines = src.read_text(encoding="utf-8").splitlines()

i = 0
table_buffer: list[str] = []
code_buffer: list[str] | None = None   # None = not in code block

while i < len(all_lines):
    raw  = all_lines[i]
    line = raw.rstrip()

    # ── Code block ──────────────────────────────────────────────────────────
    if line.startswith("```"):
        if code_buffer is None:
            # Flush pending table
            if table_buffer:
                render_table(table_buffer)
                table_buffer = []
            code_buffer = []
        else:
            render_code_block(code_buffer)
            code_buffer = None
        i += 1
        continue

    if code_buffer is not None:
        code_buffer.append(line)
        i += 1
        continue

    # ── Table row ────────────────────────────────────────────────────────────
    if line.startswith("|"):
        table_buffer.append(line)
        i += 1
        continue
    else:
        if table_buffer:
            render_table(table_buffer)
            table_buffer = []

    # ── Blank line ────────────────────────────────────────────────────────────
    if line.strip() == "":
        y -= LINE_H * 0.5
        if y < B_MARGIN:
            c.showPage()
            y = new_page()
        i += 1
        continue

    # ── Horizontal rule ───────────────────────────────────────────────────────
    if re.match(r'^---+$', line.strip()):
        y -= 6
        ensure_space(6)
        c.setStrokeColor(colors.HexColor("#CBD5E1"))
        c.line(L_MARGIN, y, PAGE_W - R_MARGIN, y)
        c.setStrokeColor(colors.black)
        y -= 6
        i += 1
        continue

    text = strip_inline(line)

    # ── H1 ────────────────────────────────────────────────────────────────────
    if text.startswith("# ") and not text.startswith("## "):
        # Page break before H1 (except first)
        if y < PAGE_H - T_MARGIN - 40:
            c.showPage()
            y = new_page()
        h1_title = safe(text[2:])
        # PDF bookmark / outline entry (level 0)
        bk = f"h1_{i}"
        c.bookmarkPage(bk)
        c.addOutlineEntry(h1_title, bk, level=0, closed=False)
        draw_line(h1_title, "Helvetica-Bold", 18,
                  extra_before=6, extra_after=8, wrap_width=60)
        # Decorative underline
        y -= 2
        c.setStrokeColor(colors.HexColor("#0EA5E9"))
        c.setLineWidth(2)
        c.line(L_MARGIN, y, L_MARGIN + USABLE_W * 0.4, y)
        c.setLineWidth(1)
        c.setStrokeColor(colors.black)
        y -= 8
        i += 1
        continue

    # ── H2 ────────────────────────────────────────────────────────────────────
    if text.startswith("## ") and not text.startswith("### "):
        h2_title = safe(text[3:])
        # PDF bookmark / outline entry (level 1)
        bk = f"h2_{i}"
        c.bookmarkPage(bk)
        c.addOutlineEntry(h2_title, bk, level=1, closed=True)
        draw_line(h2_title, "Helvetica-Bold", 14,
                  extra_before=10, extra_after=4, wrap_width=72,
                  color=colors.HexColor("#0369A1"))
        i += 1
        continue

    # ── H3 ────────────────────────────────────────────────────────────────────
    if text.startswith("### ") and not text.startswith("#### "):
        draw_line(safe(text[4:]), "Helvetica-Bold", 11,
                  extra_before=8, extra_after=2, wrap_width=82)
        i += 1
        continue

    # ── H4 ────────────────────────────────────────────────────────────────────
    if text.startswith("#### "):
        draw_line(safe(text[5:]), "Helvetica-BoldOblique", 10,
                  extra_before=6, extra_after=1, wrap_width=88)
        i += 1
        continue

    # ── Table of contents links (lines starting with number + dot or "  -")
    # Just render as body text — handled below.

    # ── Bullet list ───────────────────────────────────────────────────────────
    if line.startswith("- ") or line.startswith("* ") or line.startswith("  - "):
        bullet_text = re.sub(r'^(\s*[-*])\s+', '', line)
        indent = 16 if not line.startswith("  ") else 28
        draw_line(safe(strip_inline("* " + bullet_text)), "Helvetica", 10,
                  indent=indent, wrap_width=MAX_CHARS - 6)
        i += 1
        continue

    # ── Numbered list ─────────────────────────────────────────────────────────
    if re.match(r'^\d+\.\s', line):
        draw_line(safe(strip_inline(line)), "Helvetica", 10,
                  indent=16, wrap_width=MAX_CHARS - 6)
        i += 1
        continue

    # ── Body paragraph ────────────────────────────────────────────────────────
    draw_line(safe(text), "Helvetica", 10, wrap_width=MAX_CHARS)
    i += 1

# Flush remaining buffers
if table_buffer:
    render_table(table_buffer)
if code_buffer is not None:
    render_code_block(code_buffer)

# ---------------------------------------------------------------------------
c.save()
print(f"PDF generated: {out}")
