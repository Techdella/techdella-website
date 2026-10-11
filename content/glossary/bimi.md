---
title: "What is BIMI? Brand Logos in the Inbox, Explained"
slug: bimi
excerpt: "BIMI lets your logo appear next to your emails in Gmail, Apple Mail and Yahoo. Here is what it requires, VMC vs CMC certificates, and whether it's worth the cost."
primary_kw: bimi
secondary_kws: [bimi record, vmc certificate, cmc certificate, bimi gmail, bimi logo]
intent: informational
audience: "B2B marketing leads who run email programs"
short_definition: "BIMI (Brand Indicators for Message Identification) is an email standard that lets mailbox providers show a brand's verified logo next to its messages, once the sending domain enforces DMARC and publishes a BIMI DNS record."
taxonomies:
  glossary_category: [email-marketing]
  td_service_tag: [email-marketing]
related_terms: [spf-dkim-dmarc, email-warm-up, email-marketing]
related_service: email-marketing
internal_links:
  - /services/email-marketing/
  - /glossary/spf-dkim-dmarc/
  - /glossary/email-warm-up/
  - studios:/services/branding/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "BIMI explained: the DMARC enforcement it requires, VMC vs CMC certificates, the SVG Tiny PS logo format, an example record, and when BIMI is worth the cost."
---

BIMI (Brand Indicators for Message Identification) is a standard that lets email providers display your brand's logo next to messages from your domain, instead of a generic initial. To qualify, your domain must already pass strict email authentication: DMARC at an enforcement policy. Then you publish a BIMI record in DNS that points to your logo and, for most major inboxes, a mark certificate that proves you own it.

## Why BIMI matters

BIMI is less about the logo and more about what it proves. You can't get it without a domain locked down by [SPF, DKIM and DMARC](/glossary/spf-dkim-dmarc/) at quarantine or reject. That protects your customers from spoofed email and makes your real email easier to recognize.

:::callout tip "What Gmail requires"
Google's BIMI setup guide requires a DMARC policy of quarantine or reject applied to 100% of mail, an SVG logo of at least 96 × 96 pixels, and a Verified Mark Certificate (VMC) or Common Mark Certificate (CMC). Senders verified with a VMC also get a checkmark in Gmail ([Google Workspace Admin Help](https://knowledge.workspace.google.com/admin/security/set-up-bimi)).
:::

In a crowded inbox, a recognizable logo next to the sender name helps your newsletter, invoices and onboarding emails stand out from lookalike phishing.

## How BIMI works

When a provider receives your message, it checks DMARC first. If DMARC passes at enforcement, it looks up your BIMI record, fetches the logo and certificate, validates them and shows the logo.

| Requirement | Detail |
|---|---|
| DMARC enforcement | p=quarantine or p=reject; p=none does not qualify ([BIMI Group](https://bimigroup.org/faqs-for-senders-esps/)) |
| BIMI DNS record | A TXT record at `default._bimi.example.com` |
| Logo | SVG Tiny Portable/Secure (Tiny PS): square, no scripts, no external resources, no embedded raster images |
| Certificate | VMC (requires a registered trademark) or CMC (requires evidence the logo has been in use); issued by an approved Mark Verifying Authority; current maximum validity 398 days |
| Provider support | Gmail and Apple Mail require a certificate; Yahoo can show a self-asserted logo for qualifying bulk senders |

An example record:

```
default._bimi.example.com  TXT  "v=BIMI1; l=https://example.com/bimi/logo.svg; a=https://example.com/bimi/vmc.pem"
```

`l=` points to the SVG logo; `a=` points to the certificate file.

## Example: is BIMI worth it for a 10-person B2B company?

A hypothetical consultancy sends 3,000 emails a month: a newsletter, proposals and invoices. Its domain is at DMARC p=none.

- **Step 1, free:** move DMARC from p=none to p=reject after fixing alignment for its CRM and invoicing tool. This is the real security win.
- **Step 2, free:** publish a BIMI record and SVG logo. Yahoo may now show the logo for qualifying mail.
- **Step 3, paid:** buy a CMC for Gmail and Apple Mail. The logo has been on its website for years, so it qualifies without a trademark.

Steps 1 and 2 are worth doing for any company. Step 3 is a judgment call based on how much of your revenue depends on email being recognized and opened.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to set up BIMI

:::checklist "BIMI setup"
- Get DMARC to p=quarantine or p=reject, covering all mail, with aligned SPF or DKIM for every sender
- Check subdomains you send from inherit or publish an enforcing DMARC policy
- Create a square SVG Tiny PS version of your logo with a solid background (your designer or [branding team](studios:/services/branding/) can export it)
- Host the SVG on HTTPS with the image/svg+xml content type
- Decide on a certificate: VMC if your logo is a registered trademark, CMC if not
- Publish the BIMI TXT record at default._bimi with l= and a= values
- Validate with a BIMI checker, then send test emails to Gmail, Apple Mail and Yahoo
- Calendar the certificate renewal so the logo doesn't silently disappear
:::

:::myth-fact
Myth: BIMI improves deliverability on its own.
Fact: BIMI is a display feature. The deliverability gain comes from the DMARC enforcement it requires and from recipients recognizing you. A logo won't rescue a poor sender reputation.
:::

Before chasing BIMI, make sure your sending reputation is healthy: see [email warm-up](/glossary/email-warm-up/).

:::faq
### Does BIMI require a trademark?
Only for a Verified Mark Certificate. A Common Mark Certificate requires evidence the logo has been in public use instead. Gmail accepts both, but only VMC-verified senders get the checkmark.
### Why isn't my BIMI logo showing in Gmail?
Common causes: DMARC at p=none or below 100% coverage, no VMC or CMC, an SVG that isn't Tiny PS compliant, or low sending volume and reputation. Providers also cache logos, so changes take time.
### How much does BIMI cost?
The DNS record and SVG are free. Mark certificates are sold annually by approved authorities, and prices vary by provider and certificate type. Check current pricing with the issuers.
### Does BIMI work in Outlook?
Support varies by provider and changes over time. Check the BIMI Group's current list of supporting mailbox providers before promising stakeholders where the logo will appear.
:::

:::cta "Want a locked-down, recognizable sending domain?" "See email marketing" /services/email-marketing/
We take your domain from p=none to p=reject and set up BIMI where it's worth it.
:::
