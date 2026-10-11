---
title: "What is Server-Side Tracking? How It Works and When to Use It"
slug: server-side-tracking
excerpt: "Server-side tracking sends analytics and ad events through a server you control instead of straight from the visitor's browser. Here is what it fixes, what it doesn't, and how to set it up."
primary_kw: server-side tracking
secondary_kws: [server-side tagging, server-side gtm, sgtm, first-party tracking, client-side vs server-side tracking]
intent: informational
audience: "Marketing leads and developers responsible for analytics and ads"
short_definition: "Server-side tracking is a measurement setup where the browser sends event data to a server you control, which then processes it and forwards it to tools like Google Analytics, Google Ads or Meta, instead of each tool collecting data directly in the browser."
taxonomies:
  glossary_category: [analytics]
  td_service_tag: [marketing-automation]
related_terms: [meta-conversions-api, consent-mode-v2, attribution-modelling, performance-max]
related_service: marketing-automation
internal_links:
  - /services/marketing-automation/
  - /glossary/meta-conversions-api/
  - /glossary/consent-mode-v2/
  - /glossary/attribution-modelling/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Server-side tracking explained: client-side vs server-side, how server-side GTM works, what it fixes and what it doesn't, consent rules, and a setup checklist."
---

Server-side tracking is a way of collecting analytics and advertising data where the visitor's browser sends events to a server you control, and that server decides what to forward to Google Analytics, Google Ads, Meta and other tools. In a traditional client-side setup, every tool loads its own script in the browser and sends data straight to its vendor. Server-side tracking puts one controlled step in between.

## Why server-side tracking matters

Client-side tracking is getting less reliable and heavier.

:::callout tip "Browsers limit client-side storage"
WebKit announced in 2019 that Safari's Intelligent Tracking Prevention caps the expiry of client-side cookies at seven days, and in 2020 extended deletion to other script-writable storage after seven days of Safari use without interaction ([WebKit](https://webkit.org/blog/10218/full-third-party-cookie-blocking-and-more/)). For B2B sales cycles measured in weeks or months, a seven-day cookie can break attribution.
:::

Each extra vendor script also adds weight and slows the page. Moving tags to a server can reduce the JavaScript the browser runs, which helps Core Web Vitals.

Google's documentation for server-side tagging highlights data control in particular: only you have access to the data on your server until you choose to send it elsewhere ([Google Tag Manager](https://developers.google.com/tag-platform/tag-manager/server-side/intro)).

## How server-side tracking works

| | Client-side | Server-side |
|---|---|---|
| Where tags run | Visitor's browser, one script per tool | Your tagging server, one stream from the browser |
| Who receives raw data | Every vendor directly | Only your server; you decide what to forward |
| Cookies | Often set by JavaScript, subject to browser limits | Can be set by your own server on your domain |
| Page weight | Grows with every vendor | Fewer scripts in the browser |
| Control | Limited: vendors collect what their scripts collect | You can remove or transform fields before forwarding |
| Cost | Free tools | Hosting costs and setup time |

In Google Tag Manager's server-side model, **clients** receive incoming requests and turn them into events, then **tags** fire based on **triggers** and send data to each destination. Google strongly recommends running the tagging server on a first-party subdomain, such as `data.example.com`.

## Example: a B2B lead flow

A hypothetical SaaS company runs GA4, Google Ads, Meta and LinkedIn tags in the browser. Its demo page loads four vendor scripts, and it can't pass CRM outcomes back to ad platforms.

After moving to server-side:

1. The browser sends one GA4 event stream to `data.example.com`.
2. The tagging server forwards events to GA4 and Google Ads, and sends Lead events to Meta through the [Conversions API](/glossary/meta-conversions-api/) with a shared event_id for deduplication.
3. When a lead becomes sales-qualified in the CRM, the CRM sends that event to the server, which forwards it to Google Ads and Meta as an offline conversion.
4. Consent state from the cookie banner travels with each event, and the server drops ad events for visitors who declined.

The browser runs less vendor code, and ad platforms learn from qualified leads, not just form fills.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to set up server-side tracking

:::checklist "Server-side tracking checklist"
- Audit current tags: which tools, which events, which data fields
- Choose hosting: Google Cloud, another cloud or a managed server-side GTM host
- Map a first-party subdomain such as data.example.com to the tagging server
- Send one GA4 stream from the browser to the server container
- Recreate destination tags server-side: GA4, Google Ads, Meta CAPI, LinkedIn CAPI
- Pass consent state with every event and honor it server-side (see [Consent Mode v2](/glossary/consent-mode-v2/))
- Strip or hash personal data before forwarding, per each vendor's rules and your privacy notice
- Run old and new setups in parallel for 2–4 weeks and compare numbers before switching off client-side tags
:::

:::myth-fact
Myth: Server-side tracking lets you track people who declined cookies.
Fact: Moving data through a server doesn't change your legal obligations. Consent choices still apply, and you must honor them on the server just as you would in the browser.
:::

Better data is the foundation for honest [attribution modelling](/glossary/attribution-modelling/).

:::faq
### Is server-side tracking worth it for a small business?
If you spend meaningfully on ads, have long sales cycles or need offline conversions from your CRM, usually yes. If you run a small site with little ad spend, client-side GA4 with proper consent is often enough.
### Does server-side tracking get around ad blockers?
It can make requests look first-party, which some blockers don't stop. That is a side effect, not a goal. Respect users' choices and consent; don't design tracking to evade them.
### What does server-side tracking cost?
The software, such as server-side Google Tag Manager, is free to use, but the server isn't. Costs depend on traffic and hosting. Add setup time and ongoing maintenance to the budget.
### Can I use server-side tracking with Meta and LinkedIn?
Yes. Meta's Conversions API and LinkedIn's Conversions API both accept server events. A server container can forward to both, alongside Google tools.
:::

:::cta "Want tracking you can trust?" "See marketing automation" /services/marketing-automation/
We set up consent-aware server-side tracking and connect your CRM outcomes to your ad platforms.
:::
