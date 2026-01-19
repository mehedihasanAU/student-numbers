import json
import re
from collections import defaultdict

def normalize_key(k):
    return k.lower().replace('_', '').replace(' ', '')

try:
    with open('debug_full.json', 'r') as f:
        data = json.load(f)
except Exception as e:
    print(f"Error loading JSON: {e}")
    exit(1)

stats = defaultdict(lambda: {'total': set(), 'enrolled': set(), 'active': set()})
stats['printed_keys'] = False
stats['debug_print_count'] = 0
all_statuses = defaultdict(lambda: {'count': 0, 'examples': set()})

for row in data:
    if not isinstance(row, dict):
        continue
        
    block = "Unknown"
    status = "Unknown"
    student_id = None
    unit_code = None
    unit_type = "UNKNOWN"
    
    # Normalize keys and extract values
    if stats['printed_keys'] is False:
        print(f"Keys: {list(row.keys())}")
        stats['printed_keys'] = True

    for k, v in row.items():
        k_norm = normalize_key(k)
        
        if 'unittype' in k_norm or 'type' in k_norm:
             unit_type = str(v)
             
        if 'termperiod' in k_norm:
            # Extract Block: "2026 - Summer School" -> "Summer School"
            val = str(v).strip()
            val = re.sub(r'^\d{4}\s*-\s*', '', val)
            block = val.strip(' -')
            
        if 'scheduledunitcode' in k_norm:
             unit_code = str(v)
        
        if k_norm in ['studentnumber', 'studentid']:
            student_id = str(v)

        if 'enrolmentstatus' in k_norm and 'unit' in k_norm:
            status = str(v)

    if stats['debug_print_count'] < 5:
        print(f"DEBUG ROW: Block='{block}', ID='{student_id}', Code='{unit_code}', Status='{status}'")
        stats['debug_print_count'] += 1

    if not student_id or not unit_code:
        continue

    # Analyze Summer School Unit Types
    if "Summer" in block:
        if stats['debug_print_count'] < 10:
             print(f"DEBUG: Block='{block}', Status='{status}', UnitType='{unit_type}', UnitCode='{unit_code}'")
             stats['debug_print_count'] += 1

    if block == "Summer School" and 'enrolled' in status.lower():
        all_statuses[unit_type]['count'] += 1
        if unit_type == 'OTHER_FEE':
            all_statuses[unit_type]['examples'].add(unit_code)

print("\n--- Summer School Unit Types ---")
for ut, data in all_statuses.items():
    if data['count'] > 0:
        ex = list(data['examples'])[:5]
        print(f"{ut}: {data['count']} rows (Examples: {ex})")
