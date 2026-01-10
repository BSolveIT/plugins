#!/usr/bin/env python3
"""
PreCompact Hook: Session Diary Generator
Before context compaction, injects a prompt to create a diary entry.
Reference: https://developer.wordpress.org/plugins/
"""

import json
import sys
from datetime import datetime

def main():
    # Generate timestamp for the diary file
    timestamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    diary_path = f".claude/sessions/diary-{timestamp}.md"

    # Create the continue response with an injected prompt
    response = {
        "continue": True,
        "message": f"""IMPORTANT: Context compaction is about to occur. Before proceeding, please create a session diary entry.

Create a diary file at: {diary_path}

Include the following sections in markdown format:

# Session Diary - {datetime.now().strftime("%Y-%m-%d %H:%M")}

## What Was Accomplished
- List the main tasks completed this session

## WordPress Plugin Patterns Used
Reference: https://developer.wordpress.org/plugins/
- Security patterns (sanitization, escaping, nonces, capabilities)
- Code organization decisions
- Conditional loading implemented

## Code Separation Decisions
- What code went where (admin/, public/, includes/)
- Files kept focused and under 300 lines
- Conditional loading (only loading code when needed)

## Design Decisions & Trade-offs
- Architectural choices made and why
- Alternatives considered

## Challenges & Solutions
- Problems encountered
- How they were resolved

## User Preferences Revealed
- Coding style preferences
- Tool preferences
- Workflow preferences

## Patterns Worth Adding to CLAUDE.md
- Recurring patterns that should be documented
- Project-specific conventions discovered

After creating the diary, you may continue with compaction."""
    }

    # Output the continue response
    print(json.dumps(response))
    sys.exit(0)

if __name__ == "__main__":
    main()
