# BlePOS — Decisions

The previous decision register was absent when this session resumed. No historical entries could be recovered from the empty `.git` directory. Do not infer that undocumented implementation choices were approved decisions; record new choices here before design changes.

## D-0001 — Decision history recovery status
- Date: 2026-11-22
- Status: OPEN
- Context: The supplied development specification requires decisions to be recorded and the progress handoff referenced this file, but it was missing from the workspace.
- Decision: Start a truthful register without retroactively inventing rationales. Reconstruct historical decisions only when their evidence and reasoning can be established.
- Consequences: Existing implementation choices that lack documented rationale remain unresolved until reviewed; no architecture decision is made by this entry.
- Evidence: `docs/PROGRESS_LOG.md` L-0001.
