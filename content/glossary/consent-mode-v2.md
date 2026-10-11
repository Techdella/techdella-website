---
title: "What is Google Consent Mode v2? Basic vs Advanced"
slug: consent-mode-v2
excerpt: "Google Consent Mode v2 passes your visitors' cookie consent choices to Google tags using four parameters. Here is what each one does, basic vs advanced mode, and how to set it up."
primary_kw: consent mode v2
secondary_kws: [google consent mode v2, ad_user_data, ad_personalization, basic vs advanced consent mode, consent mode eea]
intent: informational
audience: "Marketing leads and developers running Google Analytics and Google Ads"
short_definition: "Consent Mode v2 is Google's method for passing visitors' consent choices to Google tags through four parameters, ad_storage, analytics_storage, ad_user_data and ad_personalization, so tags adjust what they store and send."
taxonomies:
  glossary_category: [analytics]
  td_service_tag: [paid-ads]
related_terms: [server-side-tracking, meta-conversions-api, performance-max, remarketing]
related_service: paid-ads
internal_links:
  - /services/paid-ads/
  - /glossary/server-side-tracking/
  - /glossary/remarketing/
  - /glossary/performance-max/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Google Consent Mode v2 explained: the four parameters, basic vs advanced mode, the default code, EEA and UK requirements, and a checklist to verify it works."
---

Google Consent Mode v2 is the way your website tells Google's tags, such as Google Analytics 4, Google Ads and Floodlight, what each visitor has agreed to in your cookie banner. It uses four consent parameters: **ad_storage**, **analytics_storage**, **ad_user_data** and **ad_personalization**. Tags read those values and change their behavior, for example not setting advertising cookies when a visitor declines. Consent Mode doesn't collect consent itself; your consent management platform (CMP) does that and passes the result on.

## Why Consent Mode v2 matters

Google updated consent mode in November 2023, adding two parameters, ad_user_data and ad_personalization ([Google for Developers](https://developers.google.com/tag-platform/security/guides/consent)). Under Google's EU user consent policy, from March 2024 advertisers needed to pass these signals for users in the European Economic Area to keep using personalization and remarketing features. For example, Customer Match in the EEA needs both ad_user_data and ad_personalization granted ([Google Ads Help](https://support.google.com/google-ads/answer/14546648)).

:::callout warning "What you lose without it"
If you advertise to EEA or UK users without valid consent signals, audiences for [remarketing](/glossary/remarketing/) and personalized ads shrink, and Google warns that continued non-compliance can lead to suspension of features such as remarketing or conversion measurement ([Google Ads Help](https://support.google.com/google-ads/answer/16724512)).
:::

## How Consent Mode v2 works

**The four parameters** ([Google consent mode overview](https://developers.google.com/tag-platform/security/concepts/consent-mode)):

| Parameter | What it controls |
|---|---|
| ad_storage | Storage, such as cookies, related to advertising |
| analytics_storage | Storage, such as cookies, related to analytics, for example visit duration |
| ad_user_data | Consent for sending user data to Google for online advertising |
| ad_personalization | Consent for personalized advertising, such as remarketing |

**Basic vs advanced mode:**

| | Basic | Advanced |
|---|---|---|
| Before the banner is answered | Google tags don't load | Tags load with consent defaulted to denied |
| If the visitor declines | Nothing is sent to Google, not even the consent state | Tags send consent state and cookieless pings |
| Conversion modeling | Based on a general model | Advertiser-specific model, more detailed |

**The default command** runs on every page before any measurement commands, with a region setting for where it applies:

```
gtag('consent', 'default', {
  'ad_storage': 'denied',
  'ad_user_data': 'denied',
  'ad_personalization': 'denied',
  'analytics_storage': 'denied',
  'region': ['GB', 'DE', 'FR'],
  'wait_for_update': 500
});
```

When the visitor makes a choice, your CMP calls `gtag('consent', 'update', {...})` with the granted values. The region list above is shortened for the example: list every country where you need denied defaults, using ISO 3166-2 codes. wait_for_update gives an asynchronously loaded banner 500 milliseconds to respond before tags fire.

## Example: checking a setup that looks right

A hypothetical B2B company installs a CMP and sees "Consent Mode: active" in its settings. A test in Tag Assistant shows:

- Defaults are set, but **after** the GA4 config tag fires, so the first hit goes out without consent state.
- The update call sends ad_storage and analytics_storage, but not ad_user_data or ad_personalization.

Both bugs are common. The fix: load the default command first in the page head, and map all four parameters in the CMP. A second test confirms the order and the values.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to implement Consent Mode v2

:::steps
1. Pick a CMP | Use a Google-certified CMP if you serve ads or measure users in the EEA, UK or Switzerland.
2. Choose basic or advanced | Decide with your privacy lead. Advanced gives better modeling; basic sends nothing before consent.
3. Set defaults first | Default all four parameters to denied for regions that need opt-in, before any Google tag fires.
4. Map all four updates | Make sure the CMP's update sends ad_storage, analytics_storage, ad_user_data and ad_personalization.
5. Mention ads personalization | Google's audit guidance says the banner's first layer must explicitly mention ads personalization.
6. Test | Use Tag Assistant to confirm order and values for accept, decline and no-choice journeys.
7. Check Google Ads and GA4 | Review consent diagnostics in both tools after a few days.
:::

:::myth-fact
Myth: Consent Mode v2 is a cookie banner.
Fact: Consent Mode only passes choices to Google tags. You still need a CMP to show the banner, record consent and control non-Google tags.
:::

If you use [server-side tracking](/glossary/server-side-tracking/), pass the consent state to the server and honor it there too.

:::faq
### Is Consent Mode v2 required outside the EEA?
Google's requirement for consent signals focuses on users in the EEA, and Google also advises denied defaults for the UK. Elsewhere it is not required by Google, but local privacy laws may still require consent. Many companies use region-specific defaults.
### What is the difference between ad_storage and ad_user_data?
ad_storage controls whether advertising cookies or identifiers can be stored on the device. ad_user_data controls whether user data can be sent to Google for advertising. A visitor can be denied on both, and they are set separately.
### Should I use basic or advanced Consent Mode?
Advanced allows cookieless pings when consent is denied, which improves conversion modeling. Basic blocks Google tags entirely until consent. The right choice depends on your legal advice and risk tolerance.
### How do I check if Consent Mode v2 is working?
Use Google Tag Assistant to inspect the consent state on page load and after each banner choice, and check the consent diagnostics in Google Ads and GA4.
:::

:::cta "Want ads and analytics that stay compliant?" "See paid ads" /services/paid-ads/
We set up Consent Mode v2 with your CMP and verify it before your campaigns depend on it.
:::
