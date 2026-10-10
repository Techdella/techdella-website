/* global TD_PREPUBLISH */
/**
 * Pre-publish checklist (§10.1) for the block editor. Plain script, no build:
 * uses the wp.plugins / wp.editor / wp.editPost / wp.data globals.
 *
 * - Shows the checklist in the document sidebar and the pre-publish panel.
 * - Locks the Publish button (lockPostSaving) until every blocking rule
 *   passes, for users who can publish and only while the post is not live.
 *   "Save draft" keeps working. The server enforces the same rules
 *   (admin/editor.php) — keep the rules in sync with admin/Checklist.php.
 * - Mirrors ACF inputs (primary_kw, hero_title, permission_confirmed) and
 *   Rank Math's description into post meta, so the publish request carries
 *   the values the server checks (ACF and Rank Math save in separate requests).
 * - Without Rank Math: SEO title/description fields with counters.
 */
(function (wp, cfg) {
  if (!wp || !wp.plugins || !wp.data || !wp.element || !cfg) {
    return;
  }
  const el = wp.element.createElement;
  const { useEffect, useState, Fragment } = wp.element;
  const { useSelect, useDispatch } = wp.data;
  const { CheckboxControl, TextControl, TextareaControl } = wp.components;
  const ed = wp.editor || {};
  const ep = wp.editPost || {};
  const DocumentPanel = ed.PluginDocumentSettingPanel || ep.PluginDocumentSettingPanel;
  const PrePublishPanel = ed.PluginPrePublishPanel || ep.PluginPrePublishPanel;
  const LOCK = 'td-prepublish-checklist';
  const placeholderRe = new RegExp(cfg.placeholderRe, 'g');

  // ---- Rules (mirror of TechDella\Core\Admin\Checklist) ----

  const parse = (html) => new DOMParser().parseFromString(`<body>${html || ''}</body>`, 'text/html');
  const textOf = (html) => parse(html).body.textContent || '';
  const normaliseHost = (host) => String(host || '').toLowerCase().trim().replace(/:\d+$/, '').replace(/^www\./, '');
  const normaliseText = (text) =>
    textOf(text).toLowerCase().replace(/['’]/g, '').replace(/[^\p{L}\p{N}]+/gu, ' ').trim();

  function containsKeyword(haystack, kw) {
    const needle = normaliseText(kw);
    return needle !== '' && ` ${normaliseText(haystack)} `.includes(` ${needle} `);
  }

  function internalLinks(html, hosts) {
    const internal = hosts.map(normaliseHost);
    const targets = new Set();
    parse(html).querySelectorAll('a[href]').forEach((a) => {
      const href = (a.getAttribute('href') || '').trim();
      if (!href || href[0] === '#' || /^(mailto|tel|javascript|data|sms):/i.test(href)) {
        return;
      }
      const abs = href.match(/^(?:[a-z][a-z0-9+.-]*:)?\/\/([^/?#:]+)/i);
      if (abs) {
        if (!internal.includes(normaliseHost(abs[1]))) {
          return;
        }
      } else if (/^[a-z][a-z0-9+.-]*:/i.test(href)) {
        return;
      }
      targets.add(href.replace(/#.*$/, '').toLowerCase());
    });
    return targets.size;
  }

  const imagesMissingAlt = (html) =>
    Array.from(parse(html).querySelectorAll('img')).filter((img) => !(img.getAttribute('alt') || '').trim()).length;

  function firstParagraph(html) {
    const p = Array.from(parse(html).querySelectorAll('p')).find((node) => (node.textContent || '').trim() !== '');
    return p ? p.textContent.trim() : '';
  }

  const placeholders = (text) => Array.from(new Set(String(text || '').match(placeholderRe) || []));

  function runChecklist(input) {
    const kw = (input.primaryKw || '').trim();
    const meta = (input.metaDescription || '').trim();
    const h1s = [input.title].concat(input.h1 || []).filter(Boolean);
    const links = internalLinks(input.content, cfg.hosts || []);
    const noAlt = imagesMissingAlt(input.content);
    const tokens = Array.from(new Set(placeholders(input.title).concat(placeholders(input.content), placeholders(meta))));
    const kwInH1 = kw !== '' && h1s.some((h) => containsKeyword(h, kw));
    const kwInIntro = kw !== '' && containsKeyword(firstParagraph(input.content), kw);
    const metaLen = Array.from(meta).length;
    const rules = [
      { id: 'meta_description', blocking: true, ok: meta !== '', label: 'Meta description filled',
        message: meta ? 'Meta description is filled.' : 'Write a meta description (140–155 characters) in the Rank Math box.' },
      { id: 'meta_length', blocking: false, ok: metaLen >= cfg.metaMin && metaLen <= cfg.metaMax, label: `Meta description ${cfg.metaMin}–${cfg.metaMax} characters`,
        message: `Meta description is ${metaLen} characters.` },
      { id: 'keyword_title', blocking: true, ok: kwInH1, label: 'Primary keyword in the H1/title',
        message: kw ? `Use "${kw}" in the title or H1.` : 'Set the primary keyword (primary_kw) first.' },
      { id: 'keyword_intro', blocking: true, ok: kwInIntro, label: 'Primary keyword in the first paragraph',
        message: kw ? `The first paragraph should use "${kw}" naturally.` : 'Set the primary keyword (primary_kw) first.' },
      { id: 'internal_links', blocking: true, ok: links >= cfg.minLinks, label: `At least ${cfg.minLinks} internal links`,
        message: `${links} internal link(s) found.` },
      { id: 'image_alt', blocking: true, ok: noAlt === 0, label: 'Every image has alt text',
        message: noAlt ? `${noAlt} image(s) have no alt text.` : 'Every image has alt text.' },
      { id: 'placeholders', blocking: true, ok: tokens.length === 0, label: 'No [CONFIRM]/[PRICE] tokens left',
        message: tokens.length ? `Resolve: ${tokens.slice(0, 5).join(', ')}` : 'No placeholder tokens left.' },
    ];
    return cfg.checklist ? rules : [];
  }

  // ---- Field reading (ACF metabox, fallback box, or post meta) ----

  function readDomField(name) {
    const acf = document.querySelector(`.acf-field[data-name="${name}"]`);
    if (acf) {
      const box = acf.querySelector('input[type="checkbox"]');
      if (box) {
        return box.checked;
      }
      const input = acf.querySelector('input:not([type="hidden"]), textarea');
      if (input) {
        return input.value;
      }
    }
    const fbBox = document.querySelector(`input[type="checkbox"][name="td_fields[${name}]"]`);
    if (fbBox) {
      return fbBox.checked;
    }
    const fb = document.querySelector(`[name="td_fields[${name}]"]:not([type="hidden"])`);
    return fb ? fb.value : undefined;
  }

  function rankMathValue(getter) {
    try {
      const rm = wp.data.select('rank-math');
      return rm && typeof rm[getter] === 'function' ? rm[getter]() : undefined;
    } catch (e) {
      return undefined;
    }
  }

  const truthy = (v) => v === true || v === 1 || v === '1' || v === 'true';

  // ---- UI ----

  function RuleList({ rules }) {
    return el(
      'ul',
      { className: 'td-checklist', style: { margin: 0 } },
      rules.map((rule) =>
        el(
          'li',
          { key: rule.id, style: { display: 'flex', gap: '8px', alignItems: 'flex-start', margin: '0 0 8px' } },
          el('span', {
            className: `dashicons ${rule.ok ? 'dashicons-yes-alt' : rule.blocking ? 'dashicons-warning' : 'dashicons-info-outline'}`,
            style: { color: rule.ok ? '#0D0D0D' : rule.blocking ? '#C23B07' : '#6B6560', flex: 'none' },
            'aria-hidden': 'true',
          }),
          el(
            'span',
            null,
            el('strong', null, rule.label),
            el('span', { className: 'screen-reader-text' }, rule.ok ? ' (done)' : ' (to do)'),
            rule.ok ? null : el('br'),
            rule.ok ? null : el('span', { style: { color: '#57534E' } }, rule.message)
          )
        )
      )
    );
  }

  function Counter({ value, min, max }) {
    const len = Array.from(value || '').length;
    const ok = len >= (min || 1) && len <= max;
    return el('p', { style: { margin: '-8px 0 12px', color: ok ? '#57534E' : '#C23B07' }, 'aria-live': 'polite' },
      `${len} characters (${min ? `${min}–` : 'up to '}${max})`);
  }

  function Checklist() {
    const [tick, setTick] = useState(0);
    useEffect(() => {
      const bump = () => setTick((t) => t + 1);
      document.addEventListener('input', bump, true);
      document.addEventListener('change', bump, true);
      const timer = window.setInterval(bump, 2000);
      return () => {
        document.removeEventListener('input', bump, true);
        document.removeEventListener('change', bump, true);
        window.clearInterval(timer);
      };
    }, []);

    const data = useSelect((select) => {
      const editor = select('core/editor');
      const post = editor.getCurrentPost() || {};
      return {
        title: editor.getEditedPostAttribute('title') || '',
        content: editor.getEditedPostContent() || '',
        meta: editor.getEditedPostAttribute('meta') || {},
        canPublish: !!(post._links && post._links['wp:action-publish']),
        isLive: editor.isCurrentPostPublished() || editor.isCurrentPostScheduled(),
      };
    }, []);
    const { editPost, lockPostSaving, unlockPostSaving } = useDispatch('core/editor');

    const meta = data.meta;
    const field = (name) => {
      const dom = readDomField(name);
      return dom !== undefined ? dom : meta[name];
    };
    const rmDesc = cfg.rankMath ? rankMathValue('getDescription') : undefined;
    const description = rmDesc !== undefined && rmDesc !== null ? String(rmDesc) : String(meta.rank_math_description || '');
    const primaryKw = String(field('primary_kw') || '');
    const heroTitle = String(field('hero_title') || '');
    const permission = truthy(field('permission_confirmed'));
    const override = truthy(meta._td_checklist_override);

    const rules = runChecklist({ title: data.title, h1: [heroTitle], content: data.content, primaryKw, metaDescription: description });
    if (cfg.permissionType) {
      rules.unshift({ id: 'permission', blocking: true, ok: permission, label: 'Permission confirmed',
        message: 'Tick the permission field once the client/person has approved this in writing. Required to publish.' });
    }
    const failing = rules.filter((r) => r.blocking && !r.ok);
    const permissionFailing = failing.some((r) => r.id === 'permission');
    const shouldLock = data.canPublish && !data.isLive && failing.length > 0 && (permissionFailing || !override);

    // Mirror unsaved ACF / Rank Math values into post meta (same request as the publish).
    useEffect(() => {
      const next = {};
      (cfg.mirror || []).forEach((key) => {
        if (!(key in meta)) {
          return;
        }
        const dom = readDomField(key);
        if (dom === undefined) {
          return;
        }
        const value = typeof meta[key] === 'boolean' ? truthy(dom) : String(dom);
        if (value !== meta[key]) {
          next[key] = value;
        }
      });
      if (cfg.rankMath && 'rank_math_description' in meta && rmDesc !== undefined && rmDesc !== null && String(rmDesc) !== meta.rank_math_description) {
        next.rank_math_description = String(rmDesc);
      }
      if (Object.keys(next).length) {
        editPost({ meta: next });
      }
    });

    useEffect(() => {
      if (shouldLock) {
        lockPostSaving(LOCK);
      } else {
        unlockPostSaving(LOCK);
      }
    }, [shouldLock]);

    const body = el(
      Fragment,
      null,
      rules.length ? el(RuleList, { rules }) : null,
      failing.length && !data.canPublish
        ? el('p', { style: { color: '#57534E' } }, 'An Editor publishes after these are done. Use "Submit for review" when you are ready.')
        : null,
      cfg.checklist && cfg.canOverride && failing.length && !permissionFailing
        ? el(CheckboxControl, {
            label: 'Admin override: publish anyway (logged with your name and the time)',
            checked: override,
            onChange: (value) => editPost({ meta: { _td_checklist_override: !!value } }),
          })
        : null,
      !cfg.rankMath && cfg.checklist && 'rank_math_title' in meta
        ? el(
            Fragment,
            null,
            el(TextControl, {
              label: 'SEO title',
              help: 'Primary keyword first, brand last: "… | Techdella". Up to 60 characters.',
              value: meta.rank_math_title || '',
              onChange: (value) => editPost({ meta: { rank_math_title: value } }),
            }),
            el(Counter, { value: meta.rank_math_title || '', max: cfg.titleMax }),
            el(TextareaControl, {
              label: 'Meta description',
              help: 'Written for clicks, 140–155 characters. Never the first sentence copied.',
              value: meta.rank_math_description || '',
              onChange: (value) => editPost({ meta: { rank_math_description: value } }),
            }),
            el(Counter, { value: meta.rank_math_description || '', min: cfg.metaMin, max: cfg.metaMax })
          )
        : null,
      el('span', { hidden: true, 'data-tick': tick })
    );

    return el(
      Fragment,
      null,
      DocumentPanel
        ? el(DocumentPanel, { name: 'td-checklist', title: `Pre-publish checklist${failing.length ? ` (${failing.length} to do)` : ''}`, className: 'td-checklist-panel', initialOpen: true }, body)
        : null,
      PrePublishPanel && rules.length
        ? el(PrePublishPanel, { title: 'TechDella checklist', initialOpen: true }, el(RuleList, { rules }))
        : null
    );
  }

  wp.plugins.registerPlugin('td-prepublish-checklist', { render: Checklist, icon: null });
})(window.wp, typeof TD_PREPUBLISH === 'undefined' ? null : TD_PREPUBLISH);
