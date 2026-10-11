---
title: "What is llms.txt? Format, Example and Google's View"
slug: llms-txt
excerpt: "llms.txt is a proposed Markdown file that gives AI tools a short map of your site. Here is the format, an example, and what Google actually says about it."
primary_kw: llms.txt
secondary_kws: [llms.txt example, llms.txt seo, llms.txt google, llms-full.txt]
intent: informational
audience: "B2B marketing leads and website owners"
short_definition: "llms.txt is a proposed Markdown file at a website's root that gives large language models a short summary of the site and links to its most useful pages, ideally in clean Markdown versions."
taxonomies:
  glossary_category: [ai-search]
  td_service_tag: [ai-search-optimization]
related_terms: [llm-visibility, geo, aeo, technical-seo]
related_service: ai-search-optimization
internal_links:
  - /services/ai-search-optimization/
  - /services/technical-seo/
  - /glossary/llm-visibility/
  - /glossary/geo/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "llms.txt explained: the exact file format, a copy-ready example, what Google says about AI text files, and when the file is worth 30 minutes of your time."
---

llms.txt is a proposed standard for a plain Markdown file, served at yoursite.com/llms.txt, that tells large language models what your site is and where its most useful content lives. Jeremy Howard published the proposal on September 3, 2024 ([llmstxt.org](https://llmstxt.org/)). It is a convention, not an official web standard, and no major search engine requires it.

## Why llms.txt matters (and why it might not)

The idea is sound. AI tools work with limited context, and HTML pages are full of navigation, scripts and footers. A short, curated Markdown index lets a tool find the right page and read a clean version of it.

The limits matter just as much.

:::callout warning "What Google says"
Google's guidance on AI features states: "You don't need to create new machine readable files, AI text files, or markup to appear in these features." It also says there are no additional technical requirements for AI Overviews or AI Mode ([Google Search Central](https://developers.google.com/search/docs/appearance/ai-features)).
:::

So llms.txt will not get you into Google's [AI Overviews](/glossary/ai-overviews/). Its value is with tools and agents that choose to read it, such as coding assistants reading developer docs. For most B2B marketing sites it is a low-cost, low-certainty addition.

## How llms.txt works

The format is defined on llmstxt.org. Sections must appear in this order:

| Part | Required? | Content |
|---|---|---|
| H1 | Yes, the only required part | The name of the site or project |
| Blockquote | No | A short summary with the key facts needed to read the rest |
| Detail paragraphs or lists | No | Any Markdown except headings: more context on how to use the links |
| H2 sections with link lists | No | Each item is a Markdown link, optionally followed by a colon and notes |
| An H2 named Optional | No | Secondary links a tool can skip when it needs a shorter context |

The proposal also recommends clean Markdown versions of key pages at the same URL plus .md (for example /services/seo/index.md). A related, unofficial convention, llms-full.txt, puts the full text of the docs in one file.

## Example: llms.txt for a B2B agency

```
# Northwind Growth

> Northwind Growth is a B2B marketing agency for SaaS and fintech companies in the US and UK. Services: SEO, paid ads, email marketing and marketing automation.

Pricing starts at a fixed monthly retainer. We do not offer one-off audits.

## Services
- [SEO](https://example.com/services/seo/): technical SEO, content and links for B2B SaaS
- [Paid ads](https://example.com/services/paid-ads/): Google, LinkedIn and Meta campaigns

## Proof
- [Case studies](https://example.com/case-studies/): results with named clients

## Optional
- [Glossary](https://example.com/glossary/): definitions of marketing terms
```

Northwind is a fictional brand. Notice the blockquote states facts a model might otherwise get wrong, and the "Optional" section marks content a tool can skip.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to create an llms.txt file

:::steps
1. Decide if it is worth it | Worth 30 minutes if you publish documentation, an API or a deep resource library. Lower priority for a ten-page brochure site.
2. Write the H1 and summary | Name, what you do, for whom, where. Use the same wording as your home page and LinkedIn.
3. List 10–30 key URLs | Services, pricing, case studies, key guides. Add a one-line note to each link.
4. Put the rest under Optional | Archives, glossaries and older posts.
5. Serve it at the root | Plain text, UTF-8, a 200 status code, not blocked by robots.txt.
6. Keep it current | Generate it from your CMS so it updates when pages change.
:::

:::myth-fact
Myth: llms.txt is like robots.txt for AI and controls what models can use.
Fact: llms.txt only suggests content to read. It grants and blocks nothing. Crawl permissions for AI bots still live in robots.txt.
:::

The fundamentals of [technical SEO](/services/technical-seo/), such as crawlable, fast, well-structured pages, do far more for [LLM visibility](/glossary/llm-visibility/) than any single file.

:::faq
### Does Google use llms.txt?
Google's AI features documentation says no AI text files or special markup are needed to appear in AI Overviews or AI Mode. Google has not documented any use of llms.txt in ranking or citations.
### Is llms.txt the same as robots.txt?
No. robots.txt tells crawlers what they may access. llms.txt is a reading guide that points tools to useful content. It has no blocking or permission function.
### Where should llms.txt go?
At the root of the domain: example.com/llms.txt. The proposal allows a subpath too, for example for a docs section. Each subdomain needs its own file.
### Should I create llms-full.txt too?
Only if you have documentation people actually load into AI tools, such as API docs. For a marketing site, one concise llms.txt is enough.
:::

:::cta "Want an AI-ready site without the guesswork?" "See AI search optimization" /services/ai-search-optimization/
We check crawl access, entity facts and answer structure, then add llms.txt only where it helps.
:::
