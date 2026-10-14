---
title: "Email Subject Line Analyzer"
slug: email-subject-line-analyzer
excerpt: "A free email subject line analyzer: score your subject and preview text out of 100 for length, spam-trigger phrases, capitals, punctuation and specificity, with a phone inbox preview."
rank_math_description: "Free email subject line analyzer: score your subject out of 100 for length, spam-trigger words, caps and preview text, then see how it looks on a phone."
tool_component: email-subject-line-analyzer
primary_kw: email subject line analyzer
secondary_kws: [subject line tester, email subject line checker, subject line grader, email preview text, spam trigger words in subject lines]
intent: transactional
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
gate: email
ad_slots: true
internal_links:
  - /services/email-marketing/
  - /glossary/spf-dkim-dmarc/
  - /glossary/a-b-testing/
  - journal:/b2b-email-deliverability/
how_to:
  - "Paste your subject line | Up to 200 characters. Write it exactly as it will go out, including any emoji or merge tag such as {{first_name}}."
  - "Add the preview text | The grey line shown after the subject in most inboxes. It is optional, but it is worth 5 points and the tool flags it if it is missing or repeats the subject."
  - "Click Analyze subject line | The first time, enter your email to unlock the results. After that, results show straight away on this browser."
  - "Read the score and the rubric | You get a score out of 100, a verdict, a phone-style inbox preview and every point given or taken away, with the reason."
  - "Fix the red rows first | Remove spam-trigger phrases, fake Re: or Fwd: prefixes and all-caps words, then trim length and add a number or a clear question."
  - "Test two versions for real | Score your two best options, then A/B test them on a slice of your list. Real sends beat any score."
faq:
  - "How does the email subject line analyzer score my subject? | It uses a fixed 100-point rubric that runs in your browser: length (25), spam-trigger phrases (20), all-caps words (15), punctuation (10), word count (10), specificity (10), emoji (5) and preview text (5). A subject that starts with Re: or Fwd: loses 15 points. Every row is shown with its points, so you can see exactly why you got your score."
  - "What is a good subject line score? | 80 or more is Strong, 60 to 79 is Good with fixes, and under 60 Needs work. Treat 80 as the bar for anything you send to your whole list. A high score means you avoided common problems. It does not mean the email will be opened, so still test your best two lines against each other."
  - "How long should an email subject line be? | Our rubric gives full points for 25 to 50 characters and partial points for 15 to 65. Gmail and other inboxes cut the subject to fit the screen, so on a phone the end of a long subject disappears. Put the words that matter first and use the preview text for the rest."
  - "Do spam trigger words really send email to spam? | Rarely on their own. Gmail, Yahoo and Outlook weigh sender reputation, authentication (SPF, DKIM, DMARC) and complaint rates far more. But phrases like act now or 100% free make readers suspicious, and suspicious readers press Report spam. That complaint rate is what hurts you, so the tool still flags them."
  - "Why does the tool flag acronyms like SEO as all caps? | The check counts any word of three or more letters written entirely in capitals. It cannot tell an acronym from shouting. One all-caps word costs 7 points and is marked Improve. If it is a real acronym your readers know, keep it and accept the small deduction."
  - "Does a high score predict my open rate? | No. No tool can predict opens, and Apple Mail Privacy Protection loads images on behalf of readers, which inflates open counts. Use the score to catch problems before you send, then judge subject lines by clicks, replies and conversions from real sends."
  - "Why do I need to enter my email? | The tool is free, and we ask for an email once per browser to show results. Your email and a one-line summary of the result (the subject and its score) go to our CRM. Your subject line is scored in your browser, not on our server."
---

The Email Subject Line Analyzer scores a subject line, and its preview text, out of 100 before you hit send. It checks length, spam-trigger phrases, all-caps words, punctuation, word count, specificity, emoji and fake reply prefixes, then shows how the line might look in a phone inbox. It is free, it runs in your browser, and the first time you use it we ask for your email to show the results.

It is built for founders and B2B marketers who write their own newsletters, nurture emails and product updates. You get a plain reason for every point, not a black-box grade.

:::takeaways
- Aim for 80 or more. That clears the common problems; it does not guarantee opens.
- Keep the subject between 25 and 50 characters and front-load the words that matter.
- Write preview text every time. Without it, inboxes show the first line of your email.
- Never start with Re: or Fwd: unless the email is a real reply or forward. Gmail's own sender guidelines say so.
- Deliverability comes first. No subject line fixes missing SPF, DKIM or DMARC.
:::

## How the email subject line analyzer works

The tool applies a fixed rubric. The code is the same for everyone and nothing is sent to an AI model. Here is exactly what it checks and how many points each check is worth.

| Check | Max points | Full points when | What costs you points |
|---|---|---|---|
| Length | 25 | 25 to 50 characters | 15 to 24 or 51 to 65 characters scores 15; anything shorter or longer scores 5 |
| Spam-trigger phrases | 20 | None of our list of 28 phrases | Each phrase found takes off 8 points |
| All-caps words | 15 | No word of 3+ letters in capitals | One such word scores 8; two or more score 0 |
| Punctuation | 10 | No exclamation marks, fewer than 2 symbols | One exclamation mark scores 6; more, or 2+ symbols ($ % * # @), score 0 |
| Word count | 10 | 4 to 9 words | Fewer or more words score 5 |
| Specificity | 10 | Contains a number, a question or a personalization tag | None of the three scores 4 |
| Emoji | 5 | 0 or 1 emoji | 2 or more score 0 |
| Preview text | 5 | Present and different from the subject | Missing or identical scores 0 |
| Re: or Fwd: prefix | penalty | Not present | Starting with Re:, Fw: or Fwd: takes off 15 |

The spam list includes phrases such as *free*, *guarantee*, *act now*, *urgent*, *click here*, *buy now*, *limited time*, *100%*, *$$$*, *congratulations* and *no obligation*. Matching is whole-phrase, so *freedom* doesn't trigger *free*.

The personalization check recognizes the merge-tag formats of common email tools: `{{first_name}}`, `*|FNAME|*`, `[first name]`, `[company]` and `%name%`.

Length is counted in characters as you see them, so an emoji counts as one character.

## How to read your results

You get a score, a verdict, a phone-style preview and a row for every check, marked Good, Improve or Fix.

| Score | Verdict | What it means | What to do |
|---|---|---|---|
| 80 to 100 | Strong | No serious problems in the rubric | Pick your two strongest lines and A/B test them |
| 60 to 79 | Good, with fixes | One or two rows are costing you | Fix any red row, then rerun |
| 0 to 59 | Needs work | Several problems, often caps, spam phrases or length | Rewrite from the reader's benefit, not the excitement |

The inbox preview is approximate. Each email app trims subjects to fit the screen and its own layout, so treat the preview as a check on what comes first, not a pixel-perfect render.

## A worked example, scored by the tool

We ran three versions of the same announcement through the analyzer. These are the tool's actual results.

**Version 1:** "🚀 HUGE NEWS!!! Don't miss our AMAZING new features" (no preview text)

- Length: 50 characters, 25/25.
- Spam phrases: none, 20/20.
- All-caps words: HUGE, NEWS, AMAZING, 0/15.
- Punctuation: 3 exclamation marks, 0/10.
- Words: 9, 10/10.
- Specificity: no number, question or tag, 4/10.
- Emoji: 1, 5/5.
- Preview text: missing, 0/5.

Total: 25 + 20 + 0 + 0 + 10 + 4 + 5 + 0 = **64, Good with fixes**. Notice that the length was fine. The capitals and exclamation marks cost 25 points between them.

**Version 2:** "Your audit report now shows AI search visibility" (no preview text)

The capitals and punctuation are gone, so those rows score 15 and 10. "AI" has only two letters, so it isn't flagged. Total: 25 + 20 + 15 + 10 + 10 + 4 + 5 + 0 = **89, Strong**.

**Version 3:** the same subject, plus the preview text "See which AI answers cite you, and which cite competitors"

The preview text adds 5 points: **94, Strong**. The only row left at Improve is specificity. Adding a number ("Your audit report now tracks 4 AI search tools") would take it to 100, but only if the number is true.

The jump from 64 to 94 came from removing noise, not from adding clever words. That is the usual pattern.

## Best practices for B2B subject lines

:::checklist "Before you send"
- Say what's inside. "Your Q4 pipeline review, in 5 minutes" beats "Big update!"
- Put the most important three or four words first, because phones cut the end.
- Write preview text that adds a second reason to open, not a repeat of the subject.
- Use a real number only when it is accurate and checkable.
- Personalize with care. A broken merge tag ("Hi {{first_name}}") is worse than none, so send yourself a test.
- Match the subject to the email. A subject that oversells gets opens once, then complaints.
- Keep one voice. If your brand doesn't use emoji anywhere else, don't start in the inbox.
:::

:::stats "What the inbox providers and regulators actually say"
5,000 | messages a day to Gmail accounts is the point where Google applies its bulk-sender rules | Google, Gmail email sender guidelines, 2026 | https://support.google.com/a/answer/81126
0.3% | user-reported spam rate in Postmaster Tools that Gmail tells senders to stay below | Google, Gmail email sender guidelines, 2026 | https://support.google.com/a/answer/81126
$53,088 | maximum penalty per email that breaks the CAN-SPAM Act, which requires subject lines to reflect the content | Federal Trade Commission, CAN-SPAM Act compliance guide, 2026 | https://www.ftc.gov/business-guidance/resources/can-spam-act-compliance-guide-business
:::

Two rules in those sources line up directly with the rubric. Google's sender guidelines tell senders not to start subject lines with Re: or Fwd: unless the message is an actual reply or forward. The FTC's guide says the subject line must accurately reflect the content of the message. That is why the analyzer treats a fake reply prefix as a penalty, not a style choice.

The spam-rate limit matters more than any single word. If readers feel tricked by a subject, they report the email, and those reports count against every email you send afterward. Our [B2B email deliverability guide](journal:/b2b-email-deliverability/) covers the full Gmail, Yahoo and Outlook rules, and our glossary entry on [SPF, DKIM and DMARC](/glossary/spf-dkim-dmarc/) shows example records.

:::cta "Want a second pair of eyes on your emails?" "See our email marketing service" /services/email-marketing/
We set up authentication, build nurture sequences and write subject lines that match what's inside.
:::

## Common mistakes

:::myth-fact
Myth: A list of banned spam words decides whether your email reaches the inbox.
Fact: Sender reputation, authentication and complaint rates matter far more. Google's sender guidelines focus on SPF, DKIM, DMARC, one-click unsubscribe and spam rates, not vocabulary. Trigger phrases matter because they make readers suspicious, and suspicious readers press Report spam.
:::

:::myth-fact
Myth: A higher open rate proves the subject line worked.
Fact: Apple Mail Privacy Protection loads email content in the background, so opens are inflated for Apple Mail users. Judge subject lines on clicks, replies and conversions, using a proper [A/B test](/glossary/a-b-testing/) with a large enough sample.
:::

:::myth-fact
Myth: Longer subject lines give you more room to persuade.
Fact: On a phone the inbox cuts long subjects, so the end is often never read. Shorter subjects with the key words first, plus preview text, carry the same message in the space you actually get.
:::

A few more we see often:

- **Writing the subject last, in a hurry.** It is the line most people read. Draft three, score them all.
- **Leaving the preview text to chance.** Without it, many inboxes show "View this email in your browser" or the alt text of your logo.
- **Copying consumer tactics into B2B email.** Countdown emoji and ALL-CAPS urgency look out of place in a finance manager's work inbox.
- **Testing two lines that are almost the same.** Test different ideas, such as a benefit versus a question, so the result teaches you something.

:::quote "Joanna Okedara-Kalu" "Founder & CEO, TechDella"
I use a score as a starting point, never the final word. The better test is the inbox itself: put your subject and preview text next to the emails your buyer actually gets that morning, and ask whether yours would earn the click. In B2B, plain and specific usually wins. Tell people what's inside and why it matters to them now.
:::

## What the analyzer can't tell you

The rubric catches common problems. It cannot know your audience, your sender reputation or whether your list opted in. It does not check deliverability, and it does not predict opens.

Use it as a pre-send check, alongside a test send to Gmail and Outlook accounts, a look at your Postmaster Tools spam rate, and a real A/B test on your list. If your emails are landing in spam regardless of the subject, start with authentication and list hygiene.
