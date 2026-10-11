---
title: "What are Google AI Overviews?"
slug: ai-overviews
excerpt: "AI Overviews are the AI-written summaries Google shows above some search results. Here is how they pick sources and what you can do about it."
primary_kw: google ai overviews
secondary_kws: [ai overviews, how to appear in ai overviews, ai overviews seo, ai mode]
intent: informational
audience: "B2B founders and marketing leads"
short_definition: "Google AI Overviews are AI-generated summaries shown at the top of some Google search results. They answer the query directly and link to supporting web pages chosen from Google's index."
taxonomies:
  glossary_category: [ai-search]
  td_service_tag: [ai-search-optimization]
related_terms: [aeo, geo, llm-visibility, technical-seo, schema-markup]
related_service: ai-search-optimization
internal_links:
  - /services/ai-search-optimization/
  - /glossary/aeo/
  - /glossary/technical-seo/
  - /services/seo/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Google AI Overviews explained: how they pick sources, what Pew found about clicks, Google's official guidance, and how to get your pages cited."
---

Google AI Overviews are AI-generated summaries that appear above the regular results for some searches. They answer the question in a few sentences or bullets and show links to the pages Google used to support the answer. Google launched them to everyone in the US on May 14, 2024 ([Google](https://blog.google/products/search/generative-ai-google-search-may-2024/)) and has since expanded them to more than 200 countries and territories.

## Why Google AI Overviews matter

AI Overviews sit in the most valuable space on the results page. When one appears, fewer people scroll to the blue links.

:::stats "AI Overviews, by the numbers" highlight=1
18% | of Google searches in a March 2025 panel of 900 US adults produced an AI summary | Pew Research Center, 2025 | https://www.pewresearch.org/short-reads/2025/07/22/google-users-are-less-likely-to-click-on-links-when-an-ai-summary-appears-in-the-results/
8% vs 15% | of visits with a click on a traditional result, with vs without an AI summary | Pew Research Center, 2025 | https://www.pewresearch.org/short-reads/2025/07/22/google-users-are-less-likely-to-click-on-links-when-an-ai-summary-appears-in-the-results/
2B+ | monthly users across 200+ countries and territories and 40 languages | Google, Q2 2025 earnings call | https://blog.google/inside-google/message-ceo/alphabet-earnings-q2-2025/
:::

The practical reading: for informational queries, being cited inside the Overview is now part of being visible. For B2B, that often means the "what is", "how to" and "best way to" queries that start a buying journey.

## How AI Overviews choose sources

Google's documentation describes the mechanics in plain terms ([Google Search Central](https://developers.google.com/search/docs/appearance/ai-features)):

| Fact from Google's guidance | What it means for you |
|---|---|
| AI Overviews and AI Mode may use "query fan-out", running related searches across subtopics | A page can be cited for a sub-question, not just the head query |
| A page must be indexed and eligible to show a snippet | Standard [technical SEO](/glossary/technical-seo/) is the entry ticket |
| There are no additional technical requirements and no special schema needed | Ignore anyone selling an "AI Overview tag" |
| Structured data should match the visible text | Markup helps understanding but cannot claim what the page doesn't say |
| nosnippet, data-nosnippet, max-snippet and noindex limit what can be shown | These controls can remove you from Overviews, too |

AI Mode is Google's separate, conversational search experience that uses the same principles at greater depth. A page that earns AI Overview citations is usually well placed for AI Mode.

## Example: winning a sub-question

A query like "how to set up DMARC for Google Workspace" fans out into sub-questions: what DMARC is, what record to publish, which policy to start with. A page with a clear H2 "Which DMARC policy should you start with?" and a two-sentence answer ("Start at p=none to collect reports, then move to quarantine and reject once all senders pass") gives Google an exact passage for one part of the Overview. The page does not need to rank first for the head term to be cited.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to get cited in Google AI Overviews

:::checklist "Eligibility and citation checklist"
- Confirm the page is indexed in Search Console's URL Inspection
- Check for nosnippet, max-snippet or data-nosnippet rules that hide the answer
- Put a direct answer in the first 40–60 words under each question heading
- Cover the likely sub-questions on the same page, each under its own heading
- Use real HTML lists and tables rather than images of text
- Keep structured data accurate and matched to visible content
- Show expertise: a named author, a reviewer, dates and sources
- Track the queries in Search Console; AI Overview traffic is reported within the Web search type
:::

:::myth-fact
Myth: You need a special file or AI markup to appear in AI Overviews.
Fact: Google states you don't need new machine-readable files, AI text files or special schema. Indexed, snippet-eligible, helpful pages are what qualify.
:::

For the wider practice of being chosen as the answer, see [AEO](/glossary/aeo/). For classic rankings that feed it, see our [SEO service](/services/seo/).

:::faq
### Can I opt out of AI Overviews?
You can limit what Google shows from a page with nosnippet, data-nosnippet or max-snippet, or remove it with noindex. These controls also affect regular snippets, so use them selectively.
### Does Search Console show AI Overview traffic?
Clicks and impressions from AI Overviews are counted in the Performance report under the Web search type. Google does not break them out as a separate filter, so compare query-level trends over time.
### Do AI Overviews appear for B2B searches?
Yes, especially for informational and how-to queries. They show less often for navigational or brand queries. Test your own priority queries, since coverage varies by topic and country.
### Is AI Mode the same as AI Overviews?
No. AI Overviews appear inside regular results. AI Mode is a separate conversational search experience with longer answers and follow-up questions. Both draw on Google's index.
:::

:::cta "Want to be the cited source?" "See AI search optimization" /services/ai-search-optimization/
We find the sub-questions your buyers trigger and rebuild the pages that should answer them.
:::
