# Reflect on Session Diaries

Analyze recent diary entries and update CLAUDE.md with learned patterns.

## Instructions

### Step 1: Read Recent Diaries

Read all diary files from `.claude/sessions/diary-*.md` (last 5-10 entries).

### Step 2: Identify Recurring Patterns

Look for patterns that appear 2+ times across diaries:

**WordPress Patterns:**
- Security practices (sanitization, escaping, nonces)
- Plugin architecture decisions
- Settings API usage patterns
- Hook registration patterns
- Conditional loading strategies

**Code Organization:**
- File structure decisions
- Code separation principles
- File size guidelines followed
- Naming conventions used

**Workflow Patterns:**
- Testing approaches that worked
- Debugging strategies
- Release processes
- WordPress.org compliance fixes

**User Preferences:**
- Coding style preferences
- Tool preferences (e.g., 7-Zip for releases)
- Communication preferences

### Step 3: Update .claude/CLAUDE.md

Add discovered patterns to the appropriate sections in `.claude/CLAUDE.md`:

```markdown
## Learned Patterns

### [Category]
- **[Pattern Name]**: [Description]
  - First observed: [date]
  - Times applied: [count]
  - Example: [brief example]
```

### Step 4: Clean Up

- Keep CLAUDE.md concise and high-signal
- Remove outdated or superseded patterns
- Consolidate similar patterns
- Archive very old diaries (move to `.claude/sessions/archive/`)

### Step 5: Report

Summarize:
- How many diaries analyzed
- How many new patterns extracted
- What was added to CLAUDE.md
- What was archived or cleaned up

## Pattern Categories to Look For

1. **WordPress Security** - sanitization, escaping, nonces, caps
2. **Plugin Architecture** - file structure, loading, hooks
3. **Code Style** - naming, formatting, organization
4. **Release Process** - packaging, versioning, submission
5. **Debugging** - common errors, solutions
6. **User Preferences** - workflow, tools, communication
7. **WordPress.org Compliance** - review feedback patterns
