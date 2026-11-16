---
version: 1
schema: test/sample.v1.json
max_output_tokens: 200
temperature: 0
variables: [occupation, isco_code]
---
A test prompt (not a real one). Answer as JSON for {{ occupation }}, ISCO-08 unit group {{ isco_code }}.
