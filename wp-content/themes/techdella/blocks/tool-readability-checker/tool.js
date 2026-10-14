// Readability Checker (§12.1). Flesch Reading Ease, Flesch-Kincaid grade,
// sentence length and passive-voice hints, computed in the browser.
import { tool, el, section, stats, checks, num, words } from '../tool-kit/kit.js';

// Syllables: a common heuristic (vowel groups, minus silent endings). Good enough for scores.
export function syllables(word) {
  let w = word.toLowerCase().replace(/[^a-z]/g, '');
  if (!w) return 0;
  if (w.length <= 3) return 1;
  w = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, '');
  const groups = w.match(/[aeiouy]{1,2}/g);
  return Math.max(1, groups ? groups.length : 1);
}

export function sentences(text) {
  return String(text)
    .replace(/\b(?:e\.g|i\.e|etc|vs|mr|mrs|ms|dr|inc|ltd)\./gi, (m) => m.replace(/\./g, ''))
    .split(/(?<=[.!?])\s+|\n{2,}|\n(?=[-*•]\s)/)
    .map((s) => s.trim())
    .filter((s) => words(s).length > 0);
}

const band = (ease) => {
  if (ease >= 80) return ['Easy', 'Plain enough for almost anyone to read quickly.'];
  if (ease >= 60) return ['Plain English', 'Clear for most adult readers. A good target for web pages and email.'];
  if (ease >= 50) return ['Fairly difficult', 'Readable, but shorter sentences and simpler words would help.'];
  if (ease >= 30) return ['Difficult', 'Hard work for many readers. Split long sentences and swap long words.'];
  return ['Very difficult', 'Reads like an academic paper. Rewrite the longest sentences first.'];
};

tool('readability-checker', {
  validate: (v) => (v.text && words(v.text).length < 20 ? { text: 'Paste at least 20 words so the scores mean something.' } : {}),
  run(v) {
    const list = sentences(v.text);
    const w = words(v.text);
    const syl = w.reduce((n, x) => n + syllables(x), 0);
    const complex = w.filter((x) => syllables(x) >= 3).length;
    const wps = w.length / Math.max(1, list.length);
    const spw = syl / Math.max(1, w.length);
    const ease = Math.max(0, Math.min(100, 206.835 - 1.015 * wps - 84.6 * spw));
    const grade = Math.max(0, 0.39 * wps + 11.8 * spw - 15.59);
    const fog = 0.4 * (wps + (100 * complex) / Math.max(1, w.length));
    const long = list.filter((s) => words(s).length > 25);
    const passive = list.filter((s) => /\b(?:am|is|are|was|were|be|been|being)\s+(?:\w+ly\s+)?\w+(?:ed|en)\b/i.test(s));
    return { words: w.length, sentences: list.length, wps, ease, grade, fog, long, passive, minutes: w.length / 230 };
  },
  render(r, out) {
    const [label, advice] = band(r.ease);
    const rows = [
      { status: r.wps <= 20 ? 'pass' : 'warn', label: `Average sentence: ${num(r.wps, 1)} words`, detail: 'Aim for 20 words or fewer on average.' },
      { status: r.long.length ? 'warn' : 'pass', label: `${r.long.length} sentence${r.long.length === 1 ? '' : 's'} over 25 words`, detail: r.long.length ? 'Listed below. Split them or cut a clause.' : 'Nice and tight.' },
      { status: r.passive.length > Math.max(1, r.sentences * 0.1) ? 'warn' : 'pass', label: `${r.passive.length} possible passive sentence${r.passive.length === 1 ? '' : 's'}`, detail: 'Passive voice hides who does what. "We fixed the bug" beats "The bug was fixed."' },
    ];
    out.append(
      section('Readability',
        el('div', { class: 'td-tool__score' }, el('strong', { text: num(r.ease) }), el('span', { text: `/ 100 Flesch Reading Ease · ${label}` })),
        el('div', { class: 'td-tool__meter', role: 'img', 'aria-label': `Reading ease ${num(r.ease)} out of 100` }, el('span', { style: `width:${Math.round(r.ease)}%` })),
        el('p', { class: 'td-tool__p', text: advice })),
      section('The numbers', stats([[num(r.grade, 1), 'Grade level (Flesch-Kincaid)'], [num(r.fog, 1), 'Gunning Fog index'], [num(r.words), 'Words'], [num(r.sentences), 'Sentences'], [`${Math.max(1, Math.round(r.minutes))} min`, 'Reading time']])),
      section('What to fix', checks(rows)),
    );
    if (r.long.length) out.append(section('Longest sentences', el('ol', { class: 'td-tool__list' }, r.long.sort((a, b) => words(b).length - words(a).length).slice(0, 5).map((s) => el('li', { text: `${s} (${words(s).length} words)` })))));
  },
  summary: (r) => `Reading ease ${num(r.ease)}/100, grade ${num(r.grade, 1)}, ${r.words} words, ${r.long.length} long sentences.`,
});
