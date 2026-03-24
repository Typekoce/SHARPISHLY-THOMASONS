<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;

/**
 * DevicesController
 * Handles detection of USB and hardware devices connected to the host.
 * 
 * Route examples:
 *   GET  /php/devices          → List all detected devices
 *   GET  /php/devices/usb      → USB devices only
 *   GET  /php/devices/detail   → Detailed information
 */
class DevicesController extends BaseController
{
    /**
     * GET /php/devices
     * Returns basic list of connected USB devices
     */
    public function index(): void
    {
        try {
            $devices = $this->getUsbDevices();

            $this->json([
                'status' => 'success',
                'module' => 'Devices',
                'count'  => count($devices),
                'devices' => $devices,
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'Failed to detect devices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /php/devices/usb
     * Returns detailed USB device information
     */
    public function usb(): void
    {
        try {
            $devices = $this->getDetailedUsbDevices();

            $this->json([
                'status' => 'success',
                'type'   => 'usb',
                'count'  => count($devices),
                'devices' => $devices
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /php/devices/detail
     * Returns very detailed information (lsusb -v style)
     */
    public function detail(): void
    {
        try {
            $output = shell_exec('lsusb -v 2>/dev/null');

            $this->json([
                'status' => 'success',
                'type'   => 'usb_detailed',
                'raw'    => $output ?: 'No detailed information available'
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'Failed to fetch detailed device info'
            ], 500);
        }
    }

    /**
     * Basic USB device detection using lsusb
     */
    private function getUsbDevices(): array
    {
        $output = shell_exec('lsusb 2>/dev/null');

        if (empty($output)) {
            return [];
        }

        $lines = explode("\n", trim($output));
        $devices = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Example line: Bus 001 Device 003: ID 0781:5581 SanDisk Corp. Ultra Fit
            if (preg_match('/Bus\s+(\d+)\s+Device\s+(\d+):\s+ID\s+([0-9a-fA-F]{4}):([0-9a-fA-F]{4})\s*(.*)/', $line, $matches)) {
                $devices[] = [
                    'bus'      => $matches[1],
                    'device'   => $matches[2],
                    'vendor_id'=> $matches[3],
                    'product_id'=> $matches[4],
                    'description' => trim($matches[5])
                ];
            }
        }

        return $devices;
    }

    /**
     * More detailed USB detection (includes manufacturer, product, etc.)
     */
    private function getDetailedUsbDevices(): array
    {
        $output = shell_exec('lsusb -t 2>/dev/null');

        if (empty($output)) {
            return $this->getUsbDevices(); // fallback
        }

        $lines = explode("\n", trim($output));
        $devices = [];
        $currentBus = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/Bus (\d+)/', $line, $m)) {
                $currentBus = $m[1];
            }

            if (preg_match('/Device (\d+): ID ([0-9a-fA-F]{4}):([0-9a-fA-F]{4})/', $line, $m)) {
                $devices[] = [
                    'bus'         => $currentBus,
                    'device'      => $m[1],
                    'vendor_id'   => $m[2],
                    'product_id'  => $m[3],
                    'description' => trim(str_replace($m[0], '', $line))
                ];
            }
        }

        return $devices;
    }
}