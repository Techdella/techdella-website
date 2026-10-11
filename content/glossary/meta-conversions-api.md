---
title: "What is the Meta Conversions API (CAPI)?"
slug: meta-conversions-api
excerpt: "The Meta Conversions API sends conversion events from your server to Meta. Here is how it works with the Pixel, how deduplication by event_id works, and how to set it up."
primary_kw: meta conversions api
secondary_kws: [meta capi, facebook conversions api, capi deduplication, event_id]
intent: informational
audience: "Marketing leads and developers running Meta ads"
short_definition: "The Meta Conversions API (CAPI) is a server-to-server connection that sends website, app, CRM and offline conversion events directly to Meta, so ad delivery and measurement don't depend only on the browser Pixel."
taxonomies:
  glossary_category: [analytics]
  td_service_tag: [paid-ads]
related_terms: [server-side-tracking, advantage-plus, consent-mode-v2, attribution-modelling]
related_service: paid-ads
internal_links:
  - /services/paid-ads/
  - /glossary/server-side-tracking/
  - /glossary/advantage-plus/
  - /services/marketing-automation/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Meta Conversions API explained: how CAPI works with the Pixel, deduplication with event_id and event_name, the 48-hour window, and a setup checklist."
---

The Meta Conversions API (CAPI) is a way to send conversion events, such as a lead, a purchase or a booked call, from your server or CRM directly to Meta, instead of relying only on the Meta Pixel running in the visitor's browser. Meta processes these server events like Pixel events, so they can be used for measurement, reporting and ad optimization ([Meta for Developers](https://developers.facebook.com/docs/marketing-api/conversions-api)).

## Why the Meta Conversions API matters

The Pixel depends on the browser. Ad blockers, browser privacy features and short cookie lifetimes stop some events from ever reaching Meta.

:::callout tip "The browser problem in one fact"
Since 2019, Safari's Intelligent Tracking Prevention has capped the expiry of client-side cookies at seven days. In 2020 WebKit added that other script-writable storage is deleted after seven days of Safari use without interaction on the site ([WebKit](https://webkit.org/blog/10218/full-third-party-cookie-blocking-and-more/)). A buyer who clicks your ad and converts two weeks later may not be connected to the click by browser data alone.
:::

For B2B, CAPI matters for a second reason: your best conversions happen offline, often several stages into your [marketing automation](/services/marketing-automation/) flow. A demo becomes a sales-qualified lead in your CRM days later. CAPI lets you send that event back to Meta so campaigns learn what a valuable lead looks like.

## How CAPI works with the Pixel

Meta's recommended setup runs both. The Pixel catches what the browser can; CAPI catches the rest and adds events the browser never sees. Because many events arrive twice, Meta has to deduplicate them.

| Step | What happens |
|---|---|
| 1. Visitor submits a form | The Pixel fires a Lead event with eventID "lead-8F3K2" |
| 2. Your server processes the form | It sends a Lead event via CAPI with event_id "lead-8F3K2" and event_name "Lead" |
| 3. Meta receives both | It matches the Pixel's eventID and event to the server's event_id and event_name |
| 4. Deduplication | Meta keeps one event, if the duplicate arrives within 48 hours of the first |

Source: [Meta deduplication guide](https://developers.facebook.com/docs/marketing-api/conversions-api/deduplicate-pixel-and-server-events). Meta also documents a fallback method that matches on event_name plus fbp or external_id, but it generally only works when the browser event arrives first. Use event_id. If you route events through a tag server, see [server-side tracking](/glossary/server-side-tracking/).

## Example: a deduplication bug

A hypothetical SaaS company launches CAPI and sees Leads in Events Manager jump from 100 a week to 190. Sales says nothing changed. The cause: the Pixel sends eventID as the form's timestamp, while the server sends its database row ID. The IDs never match, so 90 events are counted twice.

The fix is to generate one ID when the form loads, pass it to the Pixel call and in the form submission, and send the same value as event_id from the server. After the fix, reported leads settle at about 110 a week: the 100 the Pixel saw plus roughly 10 it had been missing.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to set up the Meta Conversions API

:::checklist "CAPI setup checklist"
- Choose a route: a partner integration (CRM, ecommerce platform), Meta's gateway, or [server-side tracking](/glossary/server-side-tracking/) through a tag server
- Send the same events from Pixel and server with matching event_name values
- Generate one unique event_id per event and send it from both sides
- Include customer information parameters you have consent to send, hashed as Meta specifies
- Pass fbp and fbc browser IDs from cookies to the server where available
- Respect consent: only send events your privacy notice and consent choices allow (see [Consent Mode v2](/glossary/consent-mode-v2/) for Google's equivalent)
- Send CRM stage changes, such as qualified lead or closed deal, as offline events
- Check Events Manager for deduplication and match quality before you scale spend
:::

:::myth-fact
Myth: Once you have CAPI, you can remove the Meta Pixel.
Fact: Meta recommends running both with deduplication. The Pixel captures browser context; CAPI fills the gaps and adds offline events. Together they give the most complete signal.
:::

Clean CAPI data is what makes [Advantage+](/glossary/advantage-plus/) campaigns optimize toward real pipeline.

:::faq
### What is event_id in the Conversions API?
event_id is a unique identifier you assign to each event. Meta uses it, together with event_name, to recognize when the Pixel and the server report the same action, and keeps only one. The Pixel parameter is called eventID.
### How long does Meta wait to deduplicate events?
Meta deduplicates events with the same event_id and event_name received within 48 hours of the first one. Events outside that window are counted separately.
### Does CAPI get around consent rules?
No. Server-side sending changes where data travels, not whether you may send it. You still need a lawful basis and must honor the visitor's consent choices.
### Do I need a developer to set up CAPI?
Not always. Many CRMs, ecommerce platforms and tag managers offer CAPI integrations. Custom forms and CRM stage events usually need some developer or automation work.
:::

:::cta "Want Meta to learn from your real leads?" "See paid ads" /services/paid-ads/
We connect your forms and CRM to Meta with clean, deduplicated, consent-aware events.
:::
