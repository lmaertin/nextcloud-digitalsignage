# Raspberry Pi IR Control for Nextcloud Digital Signage

Control Nextcloud Digital Signage presets using an IR remote control connected to a Raspberry Pi.

This setup uses:

- Raspberry Pi
- KY-022 IR receiver
- Passive buzzer
- Python
- `evdev`
- `gpiozero`
- Nextcloud Digital Signage API

---

# Features

- IR remote control support
- Nextcloud Digital Signage preset switching
- Audible success/error feedback
- Automatic startup via systemd
- Debounce protection
- NEC protocol auto configuration

---

# Hardware Wiring

## KY-022 IR Receiver

| KY-022 | Raspberry Pi |
|---|---|
| S | GPIO17 (Pin 11) |
| GND | GND |
| VCC | 3.3V |

---

## Passive Buzzer

| Buzzer | Raspberry Pi |
|---|---|
| + | GPIO18 (Pin 12) |
| - | GND |

GPIO18 is recommended because it supports hardware PWM.

---

# Enable GPIO IR Overlay

Edit:

```bash
sudo nano /boot/firmware/config.txt
```

Add:

```ini
dtoverlay=gpio-ir,gpio_pin=17,active_low=1
```

Reboot:

```bash
sudo reboot
```

---

# Install Required Packages

```bash
sudo apt update

sudo apt install \
    ir-keytable \
    python3-evdev \
    python3-requests \
    python3-gpiozero -y
```

---

# Test IR Reception

Run:

```bash
sudo ir-keytable -s rc0 -p nec -t
```

Press buttons on the remote control.

You should see output similar to:

```text
Scancode = 0x7076c
Scancode = 0x70714
```

---

# Python Script

Create the file:

```bash
nano ~/ir_listener.py
```

Paste:

```python
#!/usr/bin/env python3

from evdev import InputDevice, ecodes
import requests
import time
import subprocess

from gpiozero import TonalBuzzer
from gpiozero.tones import Tone
from time import sleep

# --------------------------------------------------
# Buzzer
# --------------------------------------------------

buzzer = TonalBuzzer(18)

def success_tone():

    buzzer.play(Tone("C5"))
    sleep(0.08)

    buzzer.play(Tone("E5"))
    sleep(0.08)

    buzzer.play(Tone("G5"))
    sleep(0.12)

    buzzer.stop()


def error_tone():

    buzzer.play(Tone("G4"))
    sleep(0.15)

    buzzer.play(Tone("C4"))
    sleep(0.25)

    buzzer.stop()

# --------------------------------------------------
# Configuration
# --------------------------------------------------

DEVICE = "/dev/input/event0"

CONTROL_TOKEN = "YOUR_CONTROL_TOKEN"

URL = (
    "https://your-nextcloud.example.com"
    f"/apps/digitalsignage/api/control/{CONTROL_TOKEN}/activate-preset"
)

# --------------------------------------------------
# IR codes -> presets
# --------------------------------------------------

BUTTONS = {
    0x7076C: "Default",
    0x70714: "Slides",
}

# --------------------------------------------------
# Set IR protocol
# --------------------------------------------------

print("Setting IR protocol to NEC...")

subprocess.run(
    ["ir-keytable", "-s", "rc0", "-p", "nec"],
    stdout=subprocess.DEVNULL,
    stderr=subprocess.DEVNULL,
)

# --------------------------------------------------
# Initialize device
# --------------------------------------------------

print(f"Opening device: {DEVICE}")

device = InputDevice(DEVICE)

print("IR listener started")

last_code = None
last_time = 0

DEBOUNCE_SECONDS = 0.4

# --------------------------------------------------
# Main loop
# --------------------------------------------------

for event in device.read_loop():

    if event.type != ecodes.EV_MSC:
        continue

    code = event.value

    now = time.time()

    # Debounce repeated signals
    if code == last_code and (now - last_time) < DEBOUNCE_SECONDS:
        continue

    last_code = code
    last_time = now

    print(f"IR code: {hex(code)}")

    preset = BUTTONS.get(code)

    if not preset:
        print("No action defined")
        continue

    print(f"Switching to preset: {preset}")

    try:

        response = requests.post(
            URL,
            headers={
                "Content-Type": "application/json",
            },
            json={
                "presetName": preset
            },
            timeout=5,
        )

        print(f"HTTP {response.status_code}")

        if response.ok:

            print("Success")
            success_tone()

        else:

            print("Error")
            error_tone()

    except Exception as e:

        print(f"Exception: {e}")
        error_tone()
```

---

# Make Script Executable

```bash
chmod +x ~/ir_listener.py
```

---

# Test the Script

```bash
sudo python3 ~/ir_listener.py
```

---

# Create systemd Service

Create:

```bash
sudo nano /etc/systemd/system/ir-listener.service
```

Paste:

```ini
[Unit]
Description=IR Listener for Nextcloud Digital Signage
After=network-online.target
Wants=network-online.target

[Service]
Type=simple

User=root
WorkingDirectory=/home/pi

ExecStart=/usr/bin/python3 -u /home/pi/ir_listener.py

Restart=always
RestartSec=2

StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

---

# Enable Service

```bash
sudo systemctl daemon-reload

sudo systemctl enable ir-listener

sudo systemctl start ir-listener
```

---

# Service Management

## View status

```bash
systemctl status ir-listener
```

## View logs

```bash
journalctl -u ir-listener -f
```

## Restart service

```bash
sudo systemctl restart ir-listener
```

## Stop service

```bash
sudo systemctl stop ir-listener
```

---

# Check for Undervoltage

```bash
watch -n1 vcgencmd get_throttled
```

Expected normal output:

```text
throttled=0x0
```

Undervoltage detected:

```text
throttled=0x50000
```

---

# Notes

- Samsung remotes commonly use NEC/NECX protocol.
- `gpio-ir` creates `/dev/input/event0` and `/dev/lirc0`.
- Passive buzzers require PWM-capable GPIOs.
- GPIO18 is preferred for audio output.
- The script runs as root because access to `/dev/input/event0` is required.

---

# Related Projects

- https://github.com/lmaertin/nextcloud-digitalsignage
- https://gpiozero.readthedocs.io
- https://python-evdev.readthedocs.io/en/latest/
