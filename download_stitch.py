import json
import os
import subprocess

json_file = '/home/anurag/.gemini/antigravity/brain/d40e9104-1915-44df-8521-c9a69caf3690/.system_generated/steps/384/output.txt'
out_dir = '/home/anurag/project/mitho-map/stitch_downloads'

target_ids = [
    "0189843a298e4342ae19a301ef4ad229",
    "0491b20a335b4ff09b6b5cb0c540bda6",
    "11a8fd30541449a295071b5f96aacb12",
    "3695848966614711823daddebecd9923",
    "427a7d389ef649f4bd33854f265df375",
    "5aa5fd303d404bf4b7ff56e6fbd0e57b",
    "688c2a8f5dea4a01a93a5d25fd8bb06c",
    "6abdf40c5a6d4203979010677bbbcb97",
    "73832ce6d7964aa2b1fe5e6807b2841f",
    "805fb2d971e74252aa022a954f9734cf",
    "8192b31f52294f1cab7afdd0c9dab809",
    "90cc4dd73b264c4c90eac0a7ffdd8cba",
    "b6df5cfb88e54f1fa31fadd6d2d92708",
    "d400aa0c2b004aada3bf0dd7d4dd7dee",
    "de0470efdb3c4d07a84655cdf3478b0d",
    "edcab9af03e54bd89ad4e6ef5aff2dcb",
    "20ab787e5f3347df99d200f59781d7eb",
    "28e2d82e4c684f6f91074e02983afcbe",
    "42a2124a79e145a6889f494bf7aa6410",
    "47c7d88785bb4f169b9d3e1c5c701f64",
    "5384c518d8164420b4c994a5632a8d99",
    "6ca95b95602c4901b74e7e328b9a0a07",
    "76eed3a1647a4cc4a7d13785fb9a3ee1",
    "84b51ef8430c4d1aad45809eea911558",
    "8995b2eb041742739ba5d1c205955e1a",
    "950a3d92c0e14df89b64a2d214483cca",
    "ac7b093e4e624078bcf1a53217dc2236",
    "b5b5a688ca344c29966c36950a91d09a",
    "c2a77eaf94ff486fa97d93e57ceed27c",
    "c4b88b1f106d467387135d62e9367220",
    "d1e6e0e810454476bd0c2590df2233b4",
    "e98f82e269864c25a68f26178406cb9a"
]

os.makedirs(out_dir, exist_ok=True)

with open(json_file, 'r') as f:
    data = json.load(f)

for screen in data.get('screens', []):
    screen_name = screen.get('name', '')
    screen_id = screen_name.split('/')[-1]
    
    if screen_id in target_ids:
        title = screen.get('title', screen_id).replace('/', '_').replace(' ', '_')
        
        screenshot_url = screen.get('screenshot', {}).get('downloadUrl')
        html_url = screen.get('htmlCode', {}).get('downloadUrl')
        
        if screenshot_url:
            # print(f"Downloading screenshot for {title}...")
            subprocess.run(['curl', '-s', '-L', screenshot_url, '-o', os.path.join(out_dir, f"{title}.png")])
            
        if html_url:
            # print(f"Downloading HTML for {title}...")
            subprocess.run(['curl', '-s', '-L', html_url, '-o', os.path.join(out_dir, f"{title}.html")])
            
print("Done!")
