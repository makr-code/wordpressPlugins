# ThemisDB Tag Definition

This document separates the tags that are semantically relevant for WordPress content selection from tags that are descriptive or admin-only.

## 1. Selection-relevant tags

These tags are part of the current theme and plugin selection logic and should be treated as reserved selectors for the front-end.

- `announcement` — time-bound highlight content for the announcement bar.
- `hero` — homepage hero content.
- `feature` — feature/product highlight content.
- `documentation` — documentation and knowledge-base content.
- `pricing` — pricing and plans content.
- `download` — downloadable package or binary content.
- `edition` — edition or tier content.
- `blog` — blog/news content.
- `support` — support and portal-related content.
- `recommended` — editorial recommended content.
- `lesenswert` — public recommendation / must-read content used by the German front-end and matching the theme selection whitelist.
- `editor-pick` — editor-selected content.
- `release` — release or launch content.
- `event` — event or meetup announcement.
- `conference` — conference-related content.
- `security` — critical security information or updates.
- `benchmark` — benchmark or comparison content.
- `case-study` — customer case studies.
- `trial` — evaluation or trial content.

## 2. Restricted / visibility tags

These tags are not public selection tags. They are for role-based or visibility-aware logic.

- `restricted` — content hidden from public front-end queries.
- `internal` — internal use only.
- `admin-only` — visible only to administrators.

## 3. Descriptive / optional tags

These tags may still be useful for editorial metadata, but they should not be used in primary selection logic unless a specific feature requires them.

- `webinar`
- `docker`
- `binary`
- `package`
- `compendium`
- `diagram`
- `architecture`
- `roadmap`
- `changelog`
- `community`
- `demo`
- `how-to`
- `guide`
- `customer-story`
- `support-portal`
- `ticket`
- `knowledge-base`
- `helpdesk`
- `api`
- `reference`
- `runbook`
- `audit`
- `compliance`
- `performance`
- `testing`
- `governance`
- `security`
- `benchmark`
- `analysis`
- `research`

## 4. Current rule

Use this as the hard split for WordPress selections:

- Selection=Relevant: the first group above.
- Restricted=Visibility: the second group.
- Descriptive=Optional: the third group.

Do not use free-form tags as substitutes for the selection-relevant group.

## 5. Recommended default theme set

The current ThemisDB Pulse theme should use this default set for front-end selection:

- `announcement`
- `hero`
- `feature`
- `documentation`
- `pricing`
- `download`
- `edition`
- `blog`
- `support`
- `recommended`
- `lesenswert`
- `editor-pick`
- `release`
- `event`
- `security`
- `benchmark`
- `case-study`
- `trial`
- `restricted`
