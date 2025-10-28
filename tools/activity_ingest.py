#!/usr/bin/env python3
"""Ingest community activity snapshots into MySQL.

Usage examples:
  python tools/activity_ingest.py --input data/activity_feed.json --database coc --user root --execute
  python tools/activity_ingest.py --input data/activity_feed.json > /tmp/seed.sql

If --execute is omitted the SQL is printed so you can review it first.
"""

from __future__ import annotations

import argparse
import json
import pathlib
import subprocess
import sys
from typing import Iterable, List, Mapping


def _escape(value: str) -> str:
  return value.replace("'", "''")


def build_sql(records: Iterable[Mapping[str, object]]) -> str:
  statements: List[str] = ["START TRANSACTION;"]
  for record in records:
    slug = _escape(str(record.get('community', 'general') or 'general').lower())
    posts = int(record.get('posts', 0) or 0)
    comments = int(record.get('comments', 0) or 0)
    name = _escape(record.get('name') or slug.replace('_', ' ').title())
    statements.append(
      "INSERT INTO communities (slug, name) VALUES ('{slug}', '{name}') "
      "ON DUPLICATE KEY UPDATE name = VALUES(name);".format(slug=slug, name=name)
    )
    statements.append(
      "INSERT INTO community_trends (community_id, day, posts, comments) "
      "SELECT id, CURDATE(), {posts}, {comments} FROM communities WHERE slug = '{slug}' "
      "ON DUPLICATE KEY UPDATE posts = posts + VALUES(posts), comments = comments + VALUES(comments);".format(
        posts=posts,
        comments=comments,
        slug=slug,
      )
    )
  statements.append("COMMIT;")
  return "\n".join(statements)


def main() -> int:
  parser = argparse.ArgumentParser(description="Load community activity snapshots into MySQL.")
  parser.add_argument('--input', type=pathlib.Path, default=pathlib.Path('data/activity_feed.json'))
  parser.add_argument('--database', required=False)
  parser.add_argument('--user', required=False)
  parser.add_argument('--password', required=False)
  parser.add_argument('--host', default='localhost')
  parser.add_argument('--execute', action='store_true', help='Run mysql and apply the SQL automatically.')
  args = parser.parse_args()

  if not args.input.exists():
    print(f"Input file {args.input} not found", file=sys.stderr)
    return 1

  records = json.loads(args.input.read_text(encoding='utf-8'))
  sql = build_sql(records)

  if not args.execute:
    sys.stdout.write(sql + '\n')
    return 0

  if not args.database or not args.user:
    print('--database and --user are required when using --execute', file=sys.stderr)
    return 1

  command = ['mysql', '-u', args.user, args.database, '-h', args.host]
  if args.password is not None:
    command.insert(2, f"-p{args.password}")

  try:
    proc = subprocess.run(command, input=sql.encode('utf-8'), check=True)
  except FileNotFoundError:
    print('mysql client not found on PATH.', file=sys.stderr)
    return 1
  except subprocess.CalledProcessError as exc:
    print(f'mysql exited with status {exc.returncode}', file=sys.stderr)
    return exc.returncode

  return proc.returncode


if __name__ == '__main__':
  sys.exit(main())
