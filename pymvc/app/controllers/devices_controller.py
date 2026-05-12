from app.models.devices_model import Devices
import subprocess
import json
from typing import Dict, List

class DevicesController:
    @staticmethod
    def index():
        """Return JSON with all connected devices"""
        devices = {
            "usb": get_usb_devices(),
            "disks": get_disk_devices(),
            "network": get_network_devices(),
            "total": 0
        }
        devices["total"] = sum(len(d) for d in devices.values() if isinstance(d, list))
        return json.dumps(devices, indent=2)


def get_usb_devices() -> List[Dict]:
    """Get USB devices via lsusb"""
    try:
        result = subprocess.run(['lsusb', '-v'], 
                              capture_output=True, text=True, timeout=5)
        lines = result.stdout.split('\n')
        devices = []
        current = {}
        
        for line in lines:
            if line.startswith('Bus '):
                if current:
                    devices.append(current)
                current = {'bus': line.split()[1], 'devices': []}
            elif 'idVendor' in line:
                current['vendor'] = line.split()[-1]
            elif 'idProduct' in line:
                current['product'] = line.split()[-1]
        if current:
            devices.append(current)
        return devices
    except:
        return []


def get_disk_devices() -> List[Dict]:
    """Get disk devices via lsblk"""
    try:
        result = subprocess.run(['lsblk', '-J', '-o', 'NAME,SIZE,TYPE,MOUNTPOINT'], 
                              capture_output=True, text=True, timeout=5)
        import json
        return json.loads(result.stdout)['blockdevices']
    except:
        return []


def get_network_devices() -> List[Dict]:
    """Get network interfaces via ip"""
    try:
        result = subprocess.run(['ip', '-j', 'addr'], 
                              capture_output=True, text=True, timeout=5)
        return json.loads(result.stdout)
    except:
        return []
