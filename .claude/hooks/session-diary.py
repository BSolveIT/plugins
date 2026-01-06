#!/usr/bin/env python3
"""
PreCompact Hook: Generate diary prompt before context compaction.

Uses the 'continue' JSON mechanism to inject a prompt that tells
Claude to create a session diary entry.
"""

import json
import os
import sys
from datetime import datetime
from pathlib import Path

def get_recent_session_files():
    """Get recent session log files."""
    project_root = Path(__file__).parent.parent.parent
    sessions_dir = project_root / ".claude" / "sessions"

    if not sessions_dir.exists():
        return []

    # Get .jsonl files from last 3 days
    session_files = sorted(sessions_dir.glob("*.jsonl"), reverse=True)[:3]
    return session_files

def summarize_session():
    """Create a brief summary of recent session activity."""
    session_files = get_recent_session_files()

    summary = {
        "total_entries": 0,
        "failures": 0,
        "successes": 0,
        "wp_patterns": set(),
        "files_modified": set(),
    }

    for session_file in session_files:
        try:
            with open(session_file, "r", encoding="utf-8") as f:
                for line in f:
                    try:
                        entry = json.loads(line.strip())
                        summary["total_entries"] += 1

                        if entry.get("outcome") == "failure":
                            summary["failures"] += 1
                        elif entry.get("outcome") == "success":
                            summary["successes"] += 1

                        if "wp_patterns" in entry:
                            summary["wp_patterns"].update(entry["wp_patterns"])

                        if "file" in entry:
                            summary["files_modified"].add(entry["file"])
                    except json.JSONDecodeError:
                        continue
        except IOError:
            continue

    summary["wp_patterns"] = list(summary["wp_patterns"])
    summary["files_modified"] = list(summary["files_modified"])[-10:]  # Last 10

    return summary

def main():
    # Read hook input from stdin
    try:
        hook_input = json.load(sys.stdin)
    except json.JSONDecodeError:
        hook_input = {}

    # Generate timestamp for diary file
    timestamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    diary_path = f".claude/sessions/diary-{timestamp}.md"

    # Get session summary
    summary = summarize_session()

    # Build the continue prompt
    prompt = f"""CONTEXT COMPACTION TRIGGERED - Please create a session diary entry.

Before this context is compacted, please create a diary entry at:
{diary_path}

Session activity summary:
- Total logged events: {summary['total_entries']}
- Successes: {summary['successes']}
- Failures: {summary['failures']}
- WordPress patterns used: {', '.join(summary['wp_patterns']) or 'none detected'}
- Files modified: {', '.join(summary['files_modified'][-5:]) or 'none'}

Create the diary with these sections:
## Session Summary
## What Was Accomplished
## WordPress Patterns Applied
## Challenges & Solutions
## Code Organization Decisions
## Patterns to Remember

Focus on insights that would help future sessions work more effectively on this WordPress plugin."""

    # Output the continue mechanism
    result = {
        "continue": prompt
    }

    print(json.dumps(result))
    sys.exit(0)

if __name__ == "__main__":
    main()
