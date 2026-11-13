# W04 — World Cup visual direction and match samples

## Scope and handoff

Issue: [#4](https://github.com/kleedaisuki/webdev-homework-2.0/issues/4).
The [W02 contract](contracts.md) owns routes, fields, publication rules and CSV
semantics. This document supplies a small, original page template and wireframes
for W06–W08; it does not introduce another route or data model. The actual seed is
[`data/seed/world-cup-sample.csv`](../data/seed/world-cup-sample.csv).

Build one 2026 men's World Cup selection, not a live feed or complete archive.
There are four official finished group matches and one unpublished fictional
fixture. No standings, bracket predictions, account registration, national-team
profiles or dictionary editors are needed for this iteration.

## 1. Template, terminology and assets

**Selected template: original “Pitch notebook”**, designed here rather than copied
from an external homework template. No individual-assignment template was supplied;
this original option is ready to implement without that dependency. The template
and wireframes are contributed under the repository's [GPL-3.0 license](../LICENSE).
FIFA pages are factual references, not licensed artwork or UI templates.

| Element | Decision |
| --- | --- |
| Public identity | `世界杯精选 / 2026`; clearly a coursework selection, not an official FIFA site |
| National teams | Always `国家队`; codes/names are text, never student identities |
| Coursework team | Navigation label `我们的团队`; actual team name comes from `our-team` |
| Background / text | Warm paper `#F6F7F2` / dark ink `#182B25` |
| Primary action | Pitch green `#14532D` with white text |
| Secondary text | `#4B5563` on paper or white |
| Demo label | `演示数据`, dark brown `#713F12` on pale amber `#FEF3C7` |
| Surfaces | White, 1px muted border, 8px radius; no full-page imagery |
| Type | Local system stack: `system-ui, "Microsoft YaHei", sans-serif`; body 16px, headings 24–36px |
| Spacing | 8px rhythm; 16px card padding, 24px section gaps, content max-width 1120px |
| Scores | Tabular numerals; `0` remains visible; scheduled games say `未开赛` without a fake `0:0` |

Use plain CSS and semantic server-rendered HTML with Yii helpers/ActiveForm.
Use system fonts and CSS borders rather than downloaded fonts, logos, flags or
icon packs. Any framework assets must be installed locally through Composer;
there are no CDN, embedded-video or remote-image runtime requests. Source links
are ordinary links opened only when a visitor chooses them.

At narrow widths, stack columns/cards and wrap the same navigation links; no JS
hamburger is required. Management table rows may become labelled cards. Keep
labels visible, use real links/buttons, show keyboard focus, and communicate
states with text rather than color alone. A whole row must not be the only link.

## 2. Shared navigation and page map

```text
[世界杯精选 / 2026]  [赛程赛果]  [我们的团队]  [留言]  [作业下载]  [管理登录]
 /                  /world-cup/  /team       /guestbook /downloads /admin/login
                    matches
```

The same navigation appears on public pages; the logo returns to `/`.
Implementations only enable entries when the destination actually exists.
Admin pages add `赛事管理 | 资料编辑 | 留言审核 | 返回主站 | 退出`.
`退出` is a CSRF-protected POST button, not a GET link.

```text
/ ── 赛程赛果 ── 比赛详情 ── 返回列表（保留筛选与页码）
├── 我们的团队 ── 成员详情
├── 留言 ── 提交 ── 待审核提示
├── 作业下载 ── 团队 / 个人附件
└── 管理登录 ── /admin ── 赛事管理 ── 编辑演示草稿 ── 发布
                       └── 资料编辑 ── 团队 / 成员 ── 保存
```

Public dates always say `北京时间 / UTC+08:00`. Preserve the venue's actual IANA
timezone in data; do not display a local date as if it were Beijing time.

## 3. Wireframes

The boxes describe content hierarchy, not fixed pixel dimensions. Named values
such as `[team.name]` are data bindings, not invented student information.

### 3.1 World Cup home — `/`

```text
┌ Shared public navigation ─────────────────────────────────────────────┐
│ 世界杯精选 · 2026                                                     │
│ 精选小组赛赛程与赛果（非全量档案，不提供实时比分）                      │
│ [查看赛程赛果]  [认识我们的团队]  [去留言]                            │
├ 最近更新赛果 · 北京时间 / UTC+08:00 ─────────────────────────────────┤
│ [国家队名称]  比分  [国家队名称]  小组 / 日期  [比赛详情]              │
│ Up to three published results; show 演示数据 when applicable          │
├ 项目说明 ────────────────────────────────────────────────────────────┤
│ 官方事实附来源；课堂演示独立标记。[作业下载]                           │
└ 课设项目 · 非 FIFA 官方站点 ─────────────────────────────────────────┘
```

Use `Fixture::findPublished()`, edition 2026, `finished`, ordered by
`score_updated_at DESC, id DESC`, limit 3. If empty, show `暂无已发布赛果` and keep
the list CTA. A finished draft must never leak into this summary.

### 3.2 Match list — `/world-cup/matches`

```text
┌ Shared navigation ────────────────────────────────────────────────────┐
│ 2026 精选小组赛 · 赛程赛果                                             │
│ [全部] [未开赛] [已完赛]          北京时间 / UTC+08:00                  │
├ Date / group ────────────────────────────────────────────────────────┤
│ [国家队名称]  [2 : 0 / 未开赛]  [国家队名称]                            │
│ 日期时间 · 场馆 · [官方来源 / 演示数据]   [详情]                       │
├ Other match cards ───────────────────────────────────────────────────┤
│ [上一页]  第 n 页  [下一页]                                           │
└──────────────────────────────────────────────────────────────────────┘
```

Filtering uses `status=scheduled|finished`; default is both. New filters reset
the page; pagination retains the filter. Sort by `kickoff_at ASC, id ASC`, 20 per
page. A source is a clickable link; demo cards show the text badge instead.
Empty filtered results say `暂无符合条件的比赛` and offer `查看全部`.

### 3.3 Match detail — `/world-cup/matches/{id}`

```text
┌ [返回赛程赛果] ───────────────────────────────────────────────────────┐
│ 2026 · 小组赛 · [group_name, when present]                             │
│ [官方样本 / 演示数据]   [已完赛 / 未开赛]                              │
│              [国家队名称]  2 : 0  [国家队名称]                         │
│ 开球：YYYY-MM-DD HH:mm 北京时间 / UTC+08:00                            │
│ 场馆：[venue.name] · [venue.city]                                      │
│ 官方：[FIFA 来源] · 核对日期 [source_checked_at]                        │
│ Or demo: 课堂演示，非官方比赛及赛果                                    │
└──────────────────────────────────────────────────────────────────────┘
```

For scheduled records replace the score with `未开赛`. For missing/draft/other
edition records show the same normal 404, not an unpublished preview. No public
edit controls or per-match guestbook are introduced.

### 3.4 Our team — `/team` and `/team/members/{id}`

```text
┌ Shared navigation ────────────────────────────────────────────────────┐
│ 我们的团队 · [team.name]                                              │
│ [team.summary]                                                       │
├ 团队成员 ────────────────────────────────────────────────────────────┤
│ [member.display_name] · [role]    [member.display_name] · [role]        │
│ [了解成员]                       [了解成员]                           │
│ Repeat for actual members; stack on mobile                            │
├ 团队资源 ────────────────────────────────────────────────────────────┤
│ [team link titles]      [团队作业下载]                                 │
└──────────────────────────────────────────────────────────────────────┘

Member detail: [返回我们的团队] → name → role → bio → public member links.
```

Read only `slug=our-team`; order members and links by `sort_order, id`.
No links means omit the resource list, not the person. A missing team is a normal
not-found page with a maintainer seed hint; do not invent a four-person roster.
Do not present national-team names as course members or request private contacts.

### 3.5 Match management — `/admin/matches`

```text
┌ Admin navigation ─────────────────────────────────────────────────────┐
│ 赛事管理                                           [新建演示比赛]     │
│ 对阵 / 开球（北京时间） | 类型 | 比赛状态 | 发布状态 | 操作             │
│ Official sample         | 官方 | 已完赛   | 已发布   | 只读             │
│ 演示甲队 v 演示乙队     | 演示 | 未开赛   | 草稿     | [编辑]           │
└──────────────────────────────────────────────────────────────────────┘

Editor: /admin/matches/create or /admin/matches/{id}/edit
┌ 演示数据 · [草稿 / 已发布] ───────────────────────────────────────────┐
│ 本届赛事 [2026]    国家队 [主队 ▼] [客队 ▼]    场馆 [▼]                │
│ 阶段 [小组赛]      小组 [    ]                                        │
│ 开球 [YYYY-MM-DD HH:mm:ss] 北京时间 / UTC+08:00                        │
│ 比赛状态 [未开赛 / 已完赛 ▼]    主队比分 [ ]    客队比分 [ ]            │
│ Inline validation errors beside fields                               │
│ [保存]  [取消并返回列表]                                              │
│ Saved draft only: [发布此演示比赛] (separate POST form)                 │
└──────────────────────────────────────────────────────────────────────┘
```

Keep score inputs available with a rule hint: `未开赛时留空；已完赛时两项均须填写，
0 为有效比分`. This works without JS. Saving invalid input retains entered values
and errors; no write occurs. Save and publish use separate CSRF-protected POSTs
and redirect after success; save is not publication. After saving, return to the
editor with a success notice and the publication action. Published demos retain
their badge and original publication time. Official rows have no write actions;
server-side writes to them return 403. Do not expose `is_demo`, source timestamps
or `sample_key` as editable fields.

### 3.6 Profile editing — `/admin/team`, `/admin/members/{id}/edit`

```text
┌ Admin navigation ─────────────────────────────────────────────────────┐
│ 资料编辑 · 我们的团队                                                 │
│ 团队名称 [                         ]                                 │
│ 简介     [ multiline plain text    ]                                 │
│ 团队链接：标题 [ ] URL [ ] 排序 [ ] [删除此链接 □] [+ 添加链接]         │
│ [保存团队资料] [查看公开团队页]                                       │
├ 成员列表 ────────────────────────────────────────────────────────────┤
│ [display_name] · [role]    [编辑成员资料]                              │
└──────────────────────────────────────────────────────────────────────┘

Member editor: 展示名 [ ] / 分工 [ ] / 简介 [ ] / 排序 [ ]
               personal link rows / [保存成员资料] / [查看公开成员页]
```

The link-row add action can rerender a new blank row without persistence;
no JS framework is necessary. Only an explicit delete checkbox removes an
existing link. Saving parent and links is one transaction, retaining all input
on failure. There is no member add/delete or team-slug editor in this scope.

### 3.7 Shared destinations

- `/guestbook`: approved messages above a labelled nickname/body form; success
  redirects to a `提交成功，等待审核` notice. Pending/rejected messages stay private.
- `/downloads`: separate `团队作业` / `个人作业` lists with file names and download
  links; show a useful empty state when no coursework file exists.
- `/admin/login`: username/password labels, one generic credential error, then
  `/admin` with links to the implemented management sections.

## 4. Seed selection and provenance

The CSV's 20 columns and their order match W02 §4 exactly. Stable official keys
use FIFA match numbers 001–004; these are not database IDs. All rows share the
same tournament name, including the demo, so one edition has one dictionary
entry. Country codes follow the FIFA match reports. Venue names follow the
current FIFA results/report terminology, not sponsor names or earlier previews.

Primary results source: [FIFA schedule and results](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/match-schedule-fixtures-results-teams-stadiums).
Checked on 2026-11-13; `source_checked_at` is our UTC check time, not match time
or an assertion about when FIFA last updated its page. Record finished states
explicitly; do not infer them from the current date.

| Key suffix | Official report (teams, group, local date, venue, final score) | Local kickoff reference |
| --- | --- | --- |
| 001 | [FIFA report #1](https://fdp.fifa.org/assetspublic/ce281/r12452/pdf/FullTimeMatchReport-English.pdf) | [FIFA preview](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/mexico-south-africa-preview-live-stream-team-news-tickets): 13:00 |
| 002 | [FIFA report #2](https://fdp.fifa.org/assetspublic/ce281/r12450/pdf/FullTimeMatchReport-English.pdf) | [FIFA highlights](https://www.fifa.com/en/watch/1iidGe97khg8lmdSRopdh4): 20:00 |
| 003 | [FIFA report #3](https://fdp.fifa.org/assetspublic/ce281/r12458/pdf/FullTimeMatchReport-English.pdf) | [FIFA preview](https://www.fifa.com/en/articles/canada-bosnia-and-herzegovina-live-stream-team-news-tickets): 15:00 |
| 004 | [FIFA report #4](https://fdp.fifa.org/assetspublic/ce281/r12467/pdf/FullTimeMatchReport-English.pdf) | [FIFA highlights](https://www.fifa.com/en/watch/6jzgitUqP6YyXpwwuY6VRc): 18:00 |

The FIFA results article uses Eastern Time for its schedule. The CSV instead
records each **venue-local time with its numeric offset**, supported by the
individual kickoff references above. `America/Mexico_City` applies to both Mexican
venues; Toronto and Los Angeles use their own summer offsets. Preserve the
reported host-city labels; they are not a claim about municipal stadium addresses.

`demo-classroom` is fictional: local codes DMA/DMB, explicitly named demo teams
and venue, `is_demo=1`, `scheduled/draft`, no scores or source. It is intentionally
not another official result. Import it unpublished; W07 must preserve an existing
demo on repeat imports. W08 uses this record, never an official match, for live
score-editing and publication demonstrations.

## 5. Acceptance walkthrough and downstream use

### Official sample spot checks

Two records were cross-checked against both the results page and the individual
FIFA report; local kickoff references supply the wall time:

| Record | Local date / time | Venue | Final score | UTC storage → Beijing display |
| --- | --- | --- | --- | --- |
| 001: Mexico – South Africa | June 11, 13:00, UTC−06 | Mexico City Stadium | 2:0 | June 11 19:00 → June 12 03:00 |
| 003: Canada – Bosnia and Herzegovina | June 12, 15:00, UTC−04 | Toronto Stadium | 1:1 | June 12 19:00 → June 13 03:00 |

### Design-level journeys

1. **Browse:** home → `查看赛程赛果` → four published official records in kickoff
   order → 001 detail → source and check date → back to list. The demo is absent;
   its direct public detail is 404. `未开赛` has an empty state with this seed.
2. **People and messages:** home → `我们的团队` → actual course member → back;
   shared nav → `留言` → submit nickname/body → pending notice. Team and national
   teams remain separate throughout; only approved messages become public.
3. **Demonstrate:** login → match management → `demo-classroom` → set finished
   and `0:0` → save draft → still absent publicly → publish → visible with
   `演示数据` and `0:0` → edit to `1:0` → same badge and publication time.
4. **Edit profiles:** admin → team profile → save name/summary and resource links
   → public team page; open member editor → save bio/link → public member page.
   Missing link rows alone do not delete existing links.

These are wireframe/seed acceptance checks, not claims that HTTP handlers or a
database importer already exist. Runtime journeys belong to their implementation
issues. The supplied CSV was parsed and checked for column order, stable keys,
consistent dictionaries, official/demo separation, score pairs and timezone
conversion; it yields four public matches and one hidden draft.

| Consumer | Reusable input | Implementation boundary |
| --- | --- | --- |
| W06 public UI | Sections 1–3, labels, empty states, public journeys | Use actual route IDs from W02 and real team data |
| W07 seed import | Actual CSV, provenance references, local/UTC conversions | Validate whole file, transact, preserve edited demos |
| W08 admin UI | Sections 3.5–3.6 and demo journey | Separate save/publish; never edit official facts |

No external assets, dependencies or application configuration are added by W04.
