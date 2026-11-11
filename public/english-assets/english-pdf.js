// "Ingliz tili": to'plamni PDF jadval qilib saqlash. Birinchi bosishda yuklanadi (jsPDF + IBM Plex Sans).
// Joylashuv: qisqa atamalar bir qatorga ikkitadan, uzunlari butun qatorga bittadan — har bir matnning haqiqiy eni o'lchanadi.
window.EnglishPdf = (() => {
  'use strict';
  const BASE = document.currentScript.src.replace(/[^/]*$/, '');
  const PAGE = { w: 595.28, h: 841.89, margin: 40 };        // A4, punktlarda
  const W = PAGE.w - PAGE.margin * 2;                        // jadval eni
  const NUM = 24, PAD = 6, GUTTER = 14;                      // raqam ustuni, katak ichki chekkasi, ikki yarim orasi
  const SIZE = 10, LINE = 13, ROW = 22, HEAD = 20;           // shrift, satr balandligi, bir satrli qator, sarlavha qatori
  const INK = [26, 27, 30], MUTED = [88, 91, 98], LINE_C = [216, 213, 204], BAND = [244, 243, 239];

  // Shriftda ʻ va ʼ belgilari juda keng chizilgan; ko'rinishi bir xil, eni to'g'ri bo'lgan ‘ ’ bilan almashtiramiz.
  const fix = text => String(text).replace(/\u02BB/g, '\u2018').replace(/\u02BC/g, '\u2019');

  let fonts; // ikki shrift fayli bir marta yuklanadi
  async function loadFonts() {
    const b64 = async name => {
      const buf = new Uint8Array(await (await fetch(BASE + 'vendor/' + name)).arrayBuffer());
      let s = ''; for (let i = 0; i < buf.length; i += 0x8000) s += String.fromCharCode.apply(null, buf.subarray(i, i + 0x8000));
      return btoa(s);
    };
    return fonts ||= Promise.all([b64('IBMPlexSans-Regular.ttf'), b64('IBMPlexSans-SemiBold.ttf')]);
  }

  // Atama ustunining ulushini shunday tanlaymizki, eng ko'p atama "qisqa" (yarim qatorga sig'adigan) bo'lsin.
  function chooseSplit(items, inner) {
    let best = { count: -1 };
    for (let share = 0.3; share <= 0.7001; share += 0.05) {
      const termW = inner * share - PAD * 2, defW = inner * (1 - share) - PAD * 2;
      const count = items.filter(it => it.tw <= termW && it.dw <= defW).length;
      if (count > best.count) best = { count, share };
    }
    return best.share;
  }

  // Tartibni saqlagan holda qatorlarga ajratish: uzun atama — alohida qator; qisqalari juftlanadi.
  // Yolg'iz qolgan qisqa atama keyingi qisqa atamani kutadi, shunda bo'sh yarim qator qolmaydi.
  function layout(items, isShort) {
    const rows = []; let waiting = null;
    for (const it of items) {
      if (!isShort(it)) rows.push({ full: it });
      else if (waiting) { waiting.pair.push(it); waiting = null; }
      else rows.push(waiting = { pair: [it] });
    }
    return rows;
  }

  async function make(source) {
    const verbs = source.kind === 'verbs', termLabel = verbs ? "FE'L: V1 – V2 – V3" : 'ATAMA', defLabel = verbs ? 'TARJIMA' : "TA'RIF"; // fe'lning uch shakli bitta ustunda
    const set = { title: fix(source.title), desc: fix(source.desc || ''), cards: source.cards.map(c => ({ term: fix(verbs ? `${c.term} – ${c.v2} – ${c.v3}` : c.term), def: fix(c.def) })) };
    const [regular, bold] = await loadFonts();
    const doc = new window.jspdf.jsPDF({ unit: 'pt', format: 'a4' });
    doc.addFileToVFS('PlexR.ttf', regular); doc.addFont('PlexR.ttf', 'Plex', 'normal');
    doc.addFileToVFS('PlexB.ttf', bold); doc.addFont('PlexB.ttf', 'Plex', 'bold');
    const font = style => doc.setFont('Plex', style).setFontSize(SIZE);
    const width = (text, style) => { font(style); return doc.getTextWidth(text); };

    const items = set.cards.map(c => ({ term: c.term, def: c.def, tw: width(c.term, 'bold'), dw: width(c.def, 'normal') }));
    const half = (W - GUTTER) / 2, inner = half - NUM;
    const share = chooseSplit(items, inner);
    const termW = inner * share, defW = inner * (1 - share);          // yarim qatordagi ustunlar
    const fullTermW = (W - NUM) * 0.36, fullDefW = (W - NUM) * 0.64;  // butun qatordagi ustunlar
    const rows = layout(items, it => it.tw <= termW - PAD * 2 && it.dw <= defW - PAD * 2);
    const twoColumns = rows.some(r => r.pair);

    let y = PAGE.margin, n = 0;
    const bottom = PAGE.h - PAGE.margin - 18;
    const stroke = () => doc.setDrawColor(...LINE_C).setLineWidth(0.6);

    function tableHead() {
      doc.setFillColor(...BAND).rect(PAGE.margin, y, W, HEAD, 'F');
      doc.setFont('Plex', 'bold').setFontSize(8).setTextColor(...MUTED);
      const cols = (x, tW) => { doc.text('№', x + PAD, y + 13); doc.text(termLabel, x + NUM + PAD, y + 13); doc.text(defLabel, x + NUM + tW + PAD, y + 13); };
      if (twoColumns) { cols(PAGE.margin, termW); cols(PAGE.margin + half + GUTTER, termW); } else cols(PAGE.margin, fullTermW);
      y += HEAD;
      stroke(); doc.line(PAGE.margin, y, PAGE.margin + W, y);
    }
    function cell(x, it, tW, dW, top, lines) {
      const base = top + 15;
      font('normal'); doc.setTextColor(...MUTED); doc.text(String(++n), x + PAD, base);
      font('bold'); doc.setTextColor(...INK); doc.text(lines ? lines.term : it.term, x + NUM + PAD, base, { lineHeightFactor: LINE / SIZE });
      font('normal'); doc.text(lines ? lines.def : it.def, x + NUM + tW + PAD, base, { lineHeightFactor: LINE / SIZE });
    }

    // sarlavha
    doc.setFont('Plex', 'bold').setFontSize(18).setTextColor(...INK);
    const title = doc.splitTextToSize(set.title, W);
    doc.text(title, PAGE.margin, y + 16, { lineHeightFactor: 1.2 }); y += title.length * 21.6 + 4;
    doc.setFont('Plex', 'normal').setFontSize(10).setTextColor(...MUTED);
    if (set.desc) { const d = doc.splitTextToSize(set.desc, W); doc.text(d, PAGE.margin, y + 10, { lineHeightFactor: 1.3 }); y += d.length * 13 + 2; }
    doc.text(`${set.cards.length} ta ${verbs ? "fe'l" : 'atama'}`, PAGE.margin, y + 10); y += 24;
    tableHead();

    for (const r of rows) {
      let h = ROW, lines = null;
      if (r.full) {
        font('bold'); const term = doc.splitTextToSize(r.full.term, fullTermW - PAD * 2);
        font('normal'); const def = doc.splitTextToSize(r.full.def, fullDefW - PAD * 2);
        lines = { term, def }; h = Math.max(ROW, Math.max(term.length, def.length) * LINE + 9);
      }
      if (y + h > bottom) { doc.addPage(); y = PAGE.margin; n > 0 && tableHead(); }
      if (r.full) cell(PAGE.margin, r.full, fullTermW, fullDefW, y, lines);
      else {
        cell(PAGE.margin, r.pair[0], termW, defW, y);
        if (r.pair[1]) cell(PAGE.margin + half + GUTTER, r.pair[1], termW, defW, y);
        stroke(); doc.line(PAGE.margin + half + GUTTER / 2, y, PAGE.margin + half + GUTTER / 2, y + h); // ikki yarim orasidagi chiziq
      }
      y += h;
      stroke(); doc.line(PAGE.margin, y, PAGE.margin + W, y);
    }

    const pages = doc.getNumberOfPages();
    for (let p = 1; p <= pages; p++) {
      doc.setPage(p); doc.setFont('Plex', 'normal').setFontSize(8).setTextColor(...MUTED);
      doc.text(set.title.slice(0, 80), PAGE.margin, PAGE.h - 24);
      doc.text(`${p} / ${pages}`, PAGE.margin + W, PAGE.h - 24, { align: 'right' });
    }
    return doc;
  }

  const fileName = title => (title.replace(/[\\/:*?"<>|]+/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80) || 'atamalar') + '.pdf';
  return { make, save: async set => (await make(set)).save(fileName(set.title)) };
})();
