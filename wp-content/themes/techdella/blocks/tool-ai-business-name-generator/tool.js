// AI Business Name Generator (§12.1). Names come from the server route
// (model API, key in AI_API_KEY). No client-side fallback list: if the
// route isn't configured, the visitor sees the route's message.
import { tool, el, section, copyButton } from '../tool-kit/kit.js';

tool('ai-business-name-generator', {
  busyLabel: 'Finding names…',
  async run(v, ctx) {
    const res = await ctx.api('tools/ai', {
      tool: 'ai-business-name-generator',
      input: { industry: v.industry, keywords: v.keywords, tone: v.tone || '', avoid: v.avoid || '' },
    });
    return res.data;
  },
  summary: (r) => (r.names || []).map((n) => n.name).join(', '),
  render(r, out) {
    const names = r.names || [];
    out.append(
      section(`${names.length} name ideas`,
        el('ol', { class: 'td-tool__names' }, names.map((n) => el('li', { class: 'td-tool__name' },
          el('p', { class: 'td-tool__check-label' }, el('strong', { text: n.name }), ' ', el('span', { class: 'td-tool__badge', text: n.style })),
          el('p', { class: 'td-tool__check-detail', text: n.why }))))),
      el('p', { class: 'td-tool__note', text: 'AI-generated. Check trademarks, domains and social handles before you pick one.' }),
      copyButton('Copy names', () => names.map((n) => n.name).join('\n')),
    );
  },
});
