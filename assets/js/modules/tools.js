(function () {
    const readJsonConfig = (id, fallback) => {
        const node = document.getElementById(id);
        if (!node) return fallback;
        try {
            const parsed = JSON.parse(node.textContent || '{}');
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        } catch (_error) {
            return fallback;
        }
    };
    const i18n = readJsonConfig('tools-i18n', window.toolsI18n || {});
    const toMaidenhead = (latitude, longitude, precision = 6) => {
        const safeLat = Math.max(-89.999999, Math.min(89.999999, latitude));
        const safeLon = Math.max(-179.999999, Math.min(179.999999, longitude));
        let lon = safeLon + 180;
        let lat = safeLat + 90;
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let locator = '';

        locator += letters[Math.floor(lon / 20)];
        locator += letters[Math.floor(lat / 10)];
        lon %= 20; lat %= 10;
        locator += Math.floor(lon / 2).toString();
        locator += Math.floor(lat).toString();
        lon = (lon % 2) * 12;
        lat = (lat % 1) * 24;
        locator += letters[Math.floor(lon)];
        locator += letters[Math.floor(lat)];

        return locator.slice(0, Math.max(4, Math.min(precision, 6))).toUpperCase();
    };

    const locatorToLatLon = (locator) => {
        const normalized = locator.toUpperCase().trim();
        if (!/^[A-R]{2}[0-9]{2}([A-X]{2})?$/.test(normalized)) {
            return null;
        }
        let lon = -180 + (normalized.charCodeAt(0) - 65) * 20 + Number(normalized[2]) * 2 + 1;
        let lat = -90 + (normalized.charCodeAt(1) - 65) * 10 + Number(normalized[3]) + 0.5;
        if (normalized.length === 6) {
            lon += (normalized.charCodeAt(4) - 65) * (5 / 60) + (2.5 / 60);
            lat += (normalized.charCodeAt(5) - 65) * (2.5 / 60) + (1.25 / 60);
        }
        return { lat, lon };
    };
    const haversineKm = (a, b) => {
        const r = 6371;
        const toRad = (v) => v * Math.PI / 180;
        const dLat = toRad(b.lat - a.lat);
        const dLon = toRad(b.lon - a.lon);
        const x = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLon / 2) ** 2;
        return 2 * r * Math.asin(Math.sqrt(x));
    };

    let form = null;
    let addressInput = null;
    let result = null;
    let foundAddress = null;
    let foundCoords = null;
    let foundLocator = null;
    let errorBox = null;
    let freqInput = null;
    let freqOut = null;
    let wattsInput = null;
    let dbmInput = null;
    let dbmOut = null;
    let wattsOut = null;
    let locatorA = null;
    let locatorB = null;
    let locatorDistance = null;
    let filterFreq = null;
    let filterImpedance = null;
    let filterL = null;
    let filterC = null;
    let balunSource = null;
    let balunLoad = null;
    let balunRatio = null;
    let swrInput = null;
    let swrRl = null;
    let fsplDistance = null;
    let fsplFrequency = null;
    let fsplLoss = null;
    let runtimeCapacity = null;
    let runtimeCurrent = null;
    let runtimeHours = null;
    let coaxLength = null;
    let coaxAtten = null;
    let coaxLoss = null;
    let quarterWaveFrequency = null;
    let quarterWaveVf = null;
    let quarterWaveLength = null;
    let erpPower = null;
    let erpLoss = null;
    let erpGain = null;
    let erpResult = null;
    let bandwidthRate = null;
    let bandwidthRolloff = null;
    let bandwidthResult = null;
    let dipoleFrequency = null;
    let dipoleLength = null;
    let dutyTx = null;
    let dutyPeriod = null;
    let dutyResult = null;
    let dividerVin = null;
    let dividerR1 = null;
    let dividerR2 = null;
    let dividerVout = null;
    let resistorTarget = null;
    let resistorMaxCount = null;
    let resistorBest = null;
    let mismatchSwr = null;
    let mismatchGamma = null;
    let mismatchLoss = null;
    let solarWatts = null;
    let solarHours = null;
    let solarEnergy = null;
    let mufFof2 = null;
    let mufAngle = null;
    let mufResult = null;
    let batteryVoltage = null;
    let batteryLoad = null;
    let batteryCurrent = null;
    let eirpErp = null;
    let eirpResult = null;
    let skipHeight = null;
    let skipAngle = null;
    let skipResult = null;
    let dbsumA = null;
    let dbsumB = null;
    let dbsumResult = null;
    let dbuvDbm = null;
    let dbuvResult = null;
    let gainDbd = null;
    let gainDbi = null;
    let dbwDbm = null;
    let dbwDbwInput = null;
    let dbwResult = null;
    let ohmVoltage = null;
    let ohmCurrent = null;
    let ohmResistance = null;
    let lbPtx = null;
    let lbGtx = null;
    let lbGrx = null;
    let lbLoss = null;
    let lbPrx = null;
    let xlFreq = null;
    let xlInductance = null;
    let xlResult = null;
    let xcFreq = null;
    let xcCapacitance = null;
    let xcResult = null;

    const refreshDomRefs = () => {
        form = document.getElementById('grid-tool-form');
        addressInput = document.getElementById('grid-address');
        result = document.getElementById('grid-tool-result');
        foundAddress = document.getElementById('grid-found-address');
        foundCoords = document.getElementById('grid-found-coords');
        foundLocator = document.getElementById('grid-found-locator');
        errorBox = document.getElementById('grid-tool-error');
        freqInput = document.getElementById('freq-mhz');
        freqOut = document.getElementById('freq-wavelength');
        wattsInput = document.getElementById('power-watts');
        dbmInput = document.getElementById('power-dbm-input');
        dbmOut = document.getElementById('power-dbm');
        wattsOut = document.getElementById('power-watts-out');
        locatorA = document.getElementById('locator-a');
        locatorB = document.getElementById('locator-b');
        locatorDistance = document.getElementById('locator-distance');
        filterFreq = document.getElementById('filter-freq');
        filterImpedance = document.getElementById('filter-impedance');
        filterL = document.getElementById('filter-l');
        filterC = document.getElementById('filter-c');
        balunSource = document.getElementById('balun-source');
        balunLoad = document.getElementById('balun-load');
        balunRatio = document.getElementById('balun-ratio');
        swrInput = document.getElementById('swr-input');
        swrRl = document.getElementById('swr-rl');
        fsplDistance = document.getElementById('fspl-distance');
        fsplFrequency = document.getElementById('fspl-frequency');
        fsplLoss = document.getElementById('fspl-loss');
        runtimeCapacity = document.getElementById('runtime-capacity');
        runtimeCurrent = document.getElementById('runtime-current');
        runtimeHours = document.getElementById('runtime-hours');
        coaxLength = document.getElementById('coax-length');
        coaxAtten = document.getElementById('coax-atten');
        coaxLoss = document.getElementById('coax-loss');
        quarterWaveFrequency = document.getElementById('quarter-wave-frequency');
        quarterWaveVf = document.getElementById('quarter-wave-vf');
        quarterWaveLength = document.getElementById('quarter-wave-length');
        erpPower = document.getElementById('erp-power');
        erpLoss = document.getElementById('erp-loss');
        erpGain = document.getElementById('erp-gain');
        erpResult = document.getElementById('erp-result');
        bandwidthRate = document.getElementById('bandwidth-rate');
        bandwidthRolloff = document.getElementById('bandwidth-rolloff');
        bandwidthResult = document.getElementById('bandwidth-result');
        dipoleFrequency = document.getElementById('dipole-frequency');
        dipoleLength = document.getElementById('dipole-length');
        dutyTx = document.getElementById('duty-tx');
        dutyPeriod = document.getElementById('duty-period');
        dutyResult = document.getElementById('duty-result');
        dividerVin = document.getElementById('divider-vin');
        dividerR1 = document.getElementById('divider-r1');
        dividerR2 = document.getElementById('divider-r2');
        dividerVout = document.getElementById('divider-vout');
        resistorTarget = document.getElementById('resistor-target');
        resistorMaxCount = document.getElementById('resistor-max-count');
        resistorBest = document.getElementById('resistor-best');
        mismatchSwr = document.getElementById('mismatch-swr');
        mismatchGamma = document.getElementById('mismatch-gamma');
        mismatchLoss = document.getElementById('mismatch-loss');
        solarWatts = document.getElementById('solar-watts');
        solarHours = document.getElementById('solar-hours');
        solarEnergy = document.getElementById('solar-energy');
        mufFof2 = document.getElementById('muf-fof2');
        mufAngle = document.getElementById('muf-angle');
        mufResult = document.getElementById('muf-result');
        batteryVoltage = document.getElementById('battery-voltage');
        batteryLoad = document.getElementById('battery-load');
        batteryCurrent = document.getElementById('battery-current');
        eirpErp = document.getElementById('eirp-erp');
        eirpResult = document.getElementById('eirp-result');
        skipHeight = document.getElementById('skip-height');
        skipAngle = document.getElementById('skip-angle');
        skipResult = document.getElementById('skip-result');
        dbsumA = document.getElementById('dbsum-a');
        dbsumB = document.getElementById('dbsum-b');
        dbsumResult = document.getElementById('dbsum-result');
        dbuvDbm = document.getElementById('dbuv-dbm');
        dbuvResult = document.getElementById('dbuv-result');
        gainDbd = document.getElementById('gain-dbd');
        gainDbi = document.getElementById('gain-dbi');
        dbwDbm = document.getElementById('dbw-dbm');
        dbwDbwInput = document.getElementById('dbw-dbw-input');
        dbwResult = document.getElementById('dbw-result');
        ohmVoltage = document.getElementById('ohm-voltage');
        ohmCurrent = document.getElementById('ohm-current');
        ohmResistance = document.getElementById('ohm-resistance');
        lbPtx = document.getElementById('lb-ptx');
        lbGtx = document.getElementById('lb-gtx');
        lbGrx = document.getElementById('lb-grx');
        lbLoss = document.getElementById('lb-loss');
        lbPrx = document.getElementById('lb-prx');
        xlFreq = document.getElementById('xl-freq');
        xlInductance = document.getElementById('xl-inductance');
        xlResult = document.getElementById('xl-result');
        xcFreq = document.getElementById('xc-freq');
        xcCapacitance = document.getElementById('xc-capacitance');
        xcResult = document.getElementById('xc-result');
    };

    refreshDomRefs();


    const setError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('is-hidden');
    };
    const clearError = () => errorBox?.classList.add('is-hidden');

    const initializedTools = new Set();
    const simpleToolConverters = {
        'tool-vpp-vrms': (x) => x / (2 * Math.sqrt(2)),
        'tool-vrms-vpp': (x) => x * (2 * Math.sqrt(2)),
        'tool-vpk-vrms': (x) => x / Math.sqrt(2),
        'tool-kw-w': (x) => x * 1000,
        'tool-w-kw': (x) => x / 1000,
        'tool-hz-khz': (x) => x / 1000,
        'tool-khz-mhz': (x) => x / 1000,
        'tool-mhz-ghz': (x) => x / 1000,
        'tool-in-mm': (x) => x * 25.4,
        'tool-ft-m': (x) => x * 0.3048,
        'tool-c-f': (x) => (x * 9 / 5) + 32,
        'tool-f-c': (x) => (x - 32) * 5 / 9,
        'tool-pa-db': (x) => 20 * Math.log10(Math.max(x, 1e-12) / 2e-5),
        'tool-db-pa': (x) => 2e-5 * (10 ** (x / 20)),
        'tool-j-wh': (x) => x / 3600,
        'tool-wh-j': (x) => x * 3600,
        'tool-ms-s': (x) => x / 1000,
        'tool-s-ms': (x) => x * 1000,
        'tool-rpm-rps': (x) => x / 60,
        'tool-rps-rpm': (x) => x * 60,
        'tool-sunit-dbuv': (x) => 6 + (x * 6),
        'tool-dbuv-sunit': (x) => (x - 6) / 6,
    };

    const initSimpleConverter = (toolId) => {
        const panel = document.getElementById(toolId);
        if (!panel) return;
        const input = panel.querySelector('input[id$="-in"]');
        const output = panel.querySelector('output[id$="-out"]');
        const convert = simpleToolConverters[toolId];
        if (!(input instanceof HTMLInputElement) || !output || typeof convert !== 'function') return;
        const sync = () => {
            const x = Number(input.value);
            if (!Number.isFinite(x)) {
                output.textContent = 'â€”';
                return;
            }
            const y = convert(x);
            output.textContent = Number.isFinite(y) ? y.toFixed(6).replace(/\.?(0+)$/, '') : 'â€”';
        };
        input.addEventListener('input', sync);
        sync();
    };

    const initUnitConversions = (panelId = 'tool-unit-conversions') => {
        const panel = document.getElementById(panelId);
        if (!(panel instanceof HTMLElement)) return;

        const dataNode = panel.querySelector('#unit-conv-data');
        const groupSelect = panel.querySelector('#unit-conv-group');
        const fromSelect = panel.querySelector('#unit-conv-from');
        const toSelect = panel.querySelector('#unit-conv-to');
        const input = panel.querySelector('#unit-conv-input');
        const output = panel.querySelector('#unit-conv-output');
        const swap = panel.querySelector('#unit-conv-swap');
        const presets = panel.querySelector('#unit-conv-presets');
        const reference = panel.querySelector('#unit-conv-reference');

        if (
            !(dataNode instanceof HTMLScriptElement)
            || !(groupSelect instanceof HTMLSelectElement)
            || !(fromSelect instanceof HTMLSelectElement)
            || !(toSelect instanceof HTMLSelectElement)
            || !(input instanceof HTMLInputElement)
            || !(output instanceof HTMLElement)
            || !(presets instanceof HTMLElement)
        ) {
            return;
        }

        let groups = {};
        try {
            groups = JSON.parse(dataNode.textContent || '{}');
        } catch (_) {
            groups = {};
        }

        const formatNumber = (value) => {
            if (!Number.isFinite(value)) return 'â€”';
            const abs = Math.abs(value);
            if (abs !== 0 && (abs >= 1e7 || abs < 1e-4)) {
                return value.toExponential(6).replace(/\.?0+e/, 'e');
            }
            return value.toLocaleString(undefined, {
                maximumFractionDigits: 8,
                minimumFractionDigits: 0,
            });
        };

        const toBase = (value, unit) => {
            const kind = unit?.kind || '';
            if (kind === 'dbm') return 10 ** (value / 10) / 1000;
            if (kind === 'dbw') return 10 ** (value / 10);
            if (kind === 'c') return value + 273.15;
            if (kind === 'f') return ((value - 32) * 5 / 9) + 273.15;
            if (kind === 'k') return value;
            if (kind === 'dbuv') return (value - 6) / 6;
            if (kind === 'sunit') return value;
            return value * Number(unit?.factor ?? 1);
        };

        const fromBase = (base, unit) => {
            const kind = unit?.kind || '';
            if (kind === 'dbm') return 10 * Math.log10(Math.max(base, 1e-30) * 1000);
            if (kind === 'dbw') return 10 * Math.log10(Math.max(base, 1e-30));
            if (kind === 'c') return base - 273.15;
            if (kind === 'f') return ((base - 273.15) * 9 / 5) + 32;
            if (kind === 'k') return base;
            if (kind === 'dbuv') return 6 + (base * 6);
            if (kind === 'sunit') return base;
            return base / Number(unit?.factor ?? 1);
        };

        const activeGroup = () => groups[groupSelect.value] || {};
        const activeUnits = () => activeGroup().units || {};

        const fillUnitSelects = () => {
            const units = activeUnits();
            const previousFrom = fromSelect.value;
            const previousTo = toSelect.value;
            const keys = Object.keys(units);

            fromSelect.innerHTML = '';
            toSelect.innerHTML = '';
            keys.forEach((key) => {
                const label = String(units[key]?.label || key);
                fromSelect.add(new Option(label, key));
                toSelect.add(new Option(label, key));
            });

            const defaultFrom = String(activeGroup().default_from || '');
            const defaultTo = String(activeGroup().default_to || '');
            fromSelect.value = keys.includes(previousFrom) ? previousFrom : (keys.includes(defaultFrom) ? defaultFrom : (keys[0] || ''));
            toSelect.value = keys.includes(previousTo) ? previousTo : (keys.includes(defaultTo) ? defaultTo : (keys[1] || keys[0] || ''));
        };

        const fillPresets = () => {
            const values = Array.isArray(activeGroup().presets) ? activeGroup().presets : [];
            presets.innerHTML = '';
            values.forEach((value) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'button ghost';
                button.textContent = value;
                button.addEventListener('click', () => {
                    input.value = value;
                    compute();
                });
                presets.appendChild(button);
            });
        };

        const compute = () => {
            const units = activeUnits();
            const from = units[fromSelect.value];
            const to = units[toSelect.value];
            const value = Number(input.value);
            if (!from || !to || !Number.isFinite(value)) {
                output.textContent = 'â€”';
                if (reference instanceof HTMLElement) reference.textContent = 'â€”';
                return;
            }

            const base = toBase(value, from);
            const converted = fromBase(base, to);
            output.textContent = `${formatNumber(converted)} ${to.label || ''}`.trim();
            if (reference instanceof HTMLElement) {
                reference.textContent = `${formatNumber(value)} ${from.label || fromSelect.value} = ${formatNumber(base)} base = ${formatNumber(converted)} ${to.label || toSelect.value}`;
            }
        };

        const onGroupChange = () => {
            fillUnitSelects();
            fillPresets();
            compute();
        };

        groupSelect.addEventListener('change', onGroupChange);
        fromSelect.addEventListener('change', compute);
        toSelect.addEventListener('change', compute);
        input.addEventListener('input', compute);
        swap?.addEventListener('click', () => {
            const previous = fromSelect.value;
            fromSelect.value = toSelect.value;
            toSelect.value = previous;
            compute();
        });

        fillUnitSelects();
        fillPresets();
        compute();
    };

    const toolInitializers = {
        'tool-grid': () => {
            if (!(form instanceof HTMLFormElement) || !(addressInput instanceof HTMLInputElement)) {
                return;
            }
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearError();
                result?.classList.add('is-hidden');
                const query = addressInput.value.trim();
                if (query === '') {
                    setError(i18n.err_enter_address);
                    return;
                }
                try {
                    const url = `index.php?route=tools_geocode&q=${encodeURIComponent(query)}`;
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) {
                        throw new Error(i18n.err_geocode_unavailable);
                    }
                    const payload = await response.json();
                    if (!payload?.ok) {
                        throw new Error(payload?.error || i18n.err_address_not_found);
                    }
                    const lat = Number(payload.lat);
                    const lon = Number(payload.lon);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                        throw new Error(i18n.err_invalid_coords);
                    }
                    const locator = toMaidenhead(lat, lon, 6);
                    if (foundAddress) foundAddress.textContent = payload.display_name || query;
                    if (foundCoords) foundCoords.textContent = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
                    if (foundLocator) foundLocator.textContent = locator;
                    result?.classList.remove('is-hidden');
                } catch (error) {
                    setError(error instanceof Error ? error.message : i18n.err_grid_calc);
                }
            });
        },
        'tool-freq-wave': () => {
            freqInput?.addEventListener('input', () => {
                if (!(freqInput instanceof HTMLInputElement) || !freqOut) return;
                const mhz = Number(freqInput.value);
                if (!Number.isFinite(mhz) || mhz <= 0) {
                    freqOut.textContent = 'â€”';
                    return;
                }
                const meters = 299.792458 / mhz;
                freqOut.textContent = `${meters.toFixed(3)} ${i18n.meters_unit}`;
            });
        },
        'tool-power': () => {
            wattsInput?.addEventListener('input', () => {
                if (!(wattsInput instanceof HTMLInputElement) || !dbmOut) return;
                const watts = Number(wattsInput.value);
                if (!Number.isFinite(watts) || watts <= 0) {
                    dbmOut.textContent = 'â€”';
                    return;
                }
                const dbm = 10 * Math.log10(watts * 1000);
                dbmOut.textContent = dbm.toFixed(2);
            });

            dbmInput?.addEventListener('input', () => {
                if (!(dbmInput instanceof HTMLInputElement) || !wattsOut) return;
                const dbm = Number(dbmInput.value);
                if (!Number.isFinite(dbm)) {
                    wattsOut.textContent = 'â€”';
                    return;
                }
                const watts = Math.pow(10, dbm / 10) / 1000;
                wattsOut.textContent = `${watts.toFixed(4)} ${i18n.watts_out_label || 'W'}`;
            });
        },
        'tool-unit-converter': () => initUnitConversions('tool-unit-converter'),
        'tool-unit-conversions': () => initUnitConversions('tool-unit-conversions'),
        'tool-balun': () => {
            balunSource?.addEventListener('input', computeBalun);
            balunLoad?.addEventListener('input', computeBalun);
            computeBalun();
        },
        'tool-swr': () => {
            swrInput?.addEventListener('input', computeSWR);
            computeSWR();
        },
        'tool-fspl': () => {
            fsplDistance?.addEventListener('input', computeFspl);
            fsplFrequency?.addEventListener('input', computeFspl);
            computeFspl();
        },
        'tool-runtime': () => {
            runtimeCapacity?.addEventListener('input', computeRuntime);
            runtimeCurrent?.addEventListener('input', computeRuntime);
            computeRuntime();
        },
        'tool-coax': () => {
            coaxLength?.addEventListener('input', computeCoaxLoss);
            coaxAtten?.addEventListener('input', computeCoaxLoss);
            computeCoaxLoss();
        },
        'tool-bandwidth': () => {
            bandwidthRate?.addEventListener('input', computeBandwidth);
            bandwidthRolloff?.addEventListener('input', computeBandwidth);
            computeBandwidth();
        },
        'tool-duty': () => {
            dutyTx?.addEventListener('input', computeDutyCycle);
            dutyPeriod?.addEventListener('input', computeDutyCycle);
            computeDutyCycle();
        },
        'tool-divider': () => {
            dividerVin?.addEventListener('input', computeDivider);
            dividerR1?.addEventListener('input', computeDivider);
            dividerR2?.addEventListener('input', computeDivider);
            computeDivider();
        },
        'tool-resistor-combo': () => {
            resistorTarget?.addEventListener('input', computeResistorCombo);
            resistorMaxCount?.addEventListener('input', computeResistorCombo);
            computeResistorCombo();
        },
        'tool-mismatch': () => {
            mismatchSwr?.addEventListener('input', computeMismatchLoss);
            computeMismatchLoss();
        },
        'tool-xl': () => {
            xlFreq?.addEventListener('input', computeXl);
            xlInductance?.addEventListener('input', computeXl);
            computeXl();
        },
        'tool-xc': () => {
            xcFreq?.addEventListener('input', computeXc);
            xcCapacitance?.addEventListener('input', computeXc);
            computeXc();
        },
        'tool-solar': () => {
            solarWatts?.addEventListener('input', computeSolarEnergy);
            solarHours?.addEventListener('input', computeSolarEnergy);
            computeSolarEnergy();
        },
        'tool-filter': () => {
            filterFreq?.addEventListener('input', computeFilter);
            filterImpedance?.addEventListener('input', computeFilter);
            computeFilter();
        },
        'tool-distance': () => {
            const syncDistance = () => {
                if (!(locatorA instanceof HTMLInputElement) || !(locatorB instanceof HTMLInputElement) || !locatorDistance) return;
                const p1 = locatorToLatLon(locatorA.value);
                const p2 = locatorToLatLon(locatorB.value);
                if (!p1 || !p2) {
                    locatorDistance.textContent = 'â€”';
                    return;
                }
                locatorDistance.textContent = `${haversineKm(p1, p2).toFixed(1)} ${i18n.km_unit}`;
            };
            locatorA?.addEventListener('input', syncDistance);
            locatorB?.addEventListener('input', syncDistance);
            syncDistance();
        },
        'tool-quarter-wave': () => {
            quarterWaveFrequency?.addEventListener('input', computeQuarterWave);
            quarterWaveVf?.addEventListener('input', computeQuarterWave);
            computeQuarterWave();
        },
        'tool-erp': () => {
            erpPower?.addEventListener('input', computeErp);
            erpLoss?.addEventListener('input', computeErp);
            erpGain?.addEventListener('input', computeErp);
            computeErp();
        },
        'tool-dipole': () => {
            dipoleFrequency?.addEventListener('input', computeDipole);
            computeDipole();
        },
        'tool-battery-current': () => {
            batteryVoltage?.addEventListener('input', computeBatteryCurrent);
            batteryLoad?.addEventListener('input', computeBatteryCurrent);
            computeBatteryCurrent();
        },
        'tool-muf': () => {
            mufFof2?.addEventListener('input', computeMuf);
            mufAngle?.addEventListener('input', computeMuf);
            computeMuf();
        },
        'tool-eirp': () => {
            eirpErp?.addEventListener('input', computeEirp);
            computeEirp();
        },
        'tool-skip': () => {
            skipHeight?.addEventListener('input', computeSkipDistance);
            skipAngle?.addEventListener('input', computeSkipDistance);
            computeSkipDistance();
        },
        'tool-db-sum': () => {
            dbsumA?.addEventListener('input', computeDbSum);
            dbsumB?.addEventListener('input', computeDbSum);
            computeDbSum();
        },
        'tool-dbuv': () => {
            dbuvDbm?.addEventListener('input', computeDbuv);
            computeDbuv();
        },
        'tool-gain-conv': () => {
            gainDbd?.addEventListener('input', computeGainConversion);
            computeGainConversion();
        },
        'tool-dbw': () => {
            dbwDbm?.addEventListener('input', computeDbwFromDbm);
            dbwDbwInput?.addEventListener('input', computeDbmFromDbw);
            computeDbwFromDbm();
            computeDbmFromDbw();
        },
        'tool-ohm-law': () => {
            ohmVoltage?.addEventListener('input', computeOhmLaw);
            ohmCurrent?.addEventListener('input', computeOhmLaw);
            ohmResistance?.addEventListener('input', computeOhmLaw);
            computeOhmLaw();
        },
        'tool-vpp-vrms': () => initSimpleConverter('tool-vpp-vrms'),
        'tool-vrms-vpp': () => initSimpleConverter('tool-vrms-vpp'),
        'tool-vpk-vrms': () => initSimpleConverter('tool-vpk-vrms'),
        'tool-kw-w': () => initSimpleConverter('tool-kw-w'),
        'tool-w-kw': () => initSimpleConverter('tool-w-kw'),
        'tool-hz-khz': () => initSimpleConverter('tool-hz-khz'),
        'tool-khz-mhz': () => initSimpleConverter('tool-khz-mhz'),
        'tool-mhz-ghz': () => initSimpleConverter('tool-mhz-ghz'),
        'tool-in-mm': () => initSimpleConverter('tool-in-mm'),
        'tool-ft-m': () => initSimpleConverter('tool-ft-m'),
        'tool-c-f': () => initSimpleConverter('tool-c-f'),
        'tool-f-c': () => initSimpleConverter('tool-f-c'),
        'tool-pa-db': () => initSimpleConverter('tool-pa-db'),
        'tool-db-pa': () => initSimpleConverter('tool-db-pa'),
        'tool-j-wh': () => initSimpleConverter('tool-j-wh'),
        'tool-wh-j': () => initSimpleConverter('tool-wh-j'),
        'tool-ms-s': () => initSimpleConverter('tool-ms-s'),
        'tool-s-ms': () => initSimpleConverter('tool-s-ms'),
        'tool-rpm-rps': () => initSimpleConverter('tool-rpm-rps'),
        'tool-rps-rpm': () => initSimpleConverter('tool-rps-rpm'),
        'tool-sunit-dbuv': () => initSimpleConverter('tool-sunit-dbuv'),
        'tool-dbuv-sunit': () => initSimpleConverter('tool-dbuv-sunit'),
        'tool-link-budget': () => {
            lbPtx?.addEventListener('input', computeLinkBudget);
            lbGtx?.addEventListener('input', computeLinkBudget);
            lbGrx?.addEventListener('input', computeLinkBudget);
            lbLoss?.addEventListener('input', computeLinkBudget);
            computeLinkBudget();
        },
    };


    const initToolIfNeeded = (id) => {
        if (initializedTools.has(id)) return;
        const initializer = toolInitializers[id];
        if (typeof initializer === 'function') {
            initializer();
            initializedTools.add(id);
        }
    };
    const formatOhms = (value) => {
        if (!Number.isFinite(value)) {
            return 'â€”';
        }
        if (value >= 1000) {
            return `${(value / 1000).toFixed(3)} kΩ`;
        }
        return `${value.toFixed(2)} Ω`;
    };

    const computeFilter = () => {
        if (!(filterFreq instanceof HTMLInputElement) || !(filterImpedance instanceof HTMLInputElement) || !filterL || !filterC) return;
        const fMHz = Number(filterFreq.value);
        const z = Number(filterImpedance.value);
        if (!Number.isFinite(fMHz) || fMHz <= 0 || !Number.isFinite(z) || z <= 0) {
            filterL.textContent = 'â€”';
            filterC.textContent = 'â€”';
            return;
        }
        const f = fMHz * 1e6;
        const lHenrys = z / (2 * Math.PI * f);
        const cFarads = 1 / (2 * Math.PI * f * z);
        filterL.textContent = `${(lHenrys * 1e6).toFixed(3)} ÂµH`;
        filterC.textContent = `${(cFarads * 1e12).toFixed(2)} pF`;
    };
    const computeBalun = () => {
        if (!(balunSource instanceof HTMLInputElement) || !(balunLoad instanceof HTMLInputElement) || !balunRatio) return;
        const zin = Number(balunSource.value);
        const zout = Number(balunLoad.value);
        if (!Number.isFinite(zin) || zin <= 0 || !Number.isFinite(zout) || zout <= 0) {
            balunRatio.textContent = 'â€”';
            return;
        }
        const ratio = Math.sqrt(zout / zin);
        const powerRatio = zout / zin;
        balunRatio.textContent = `1:${ratio.toFixed(2)} (Z ${zin}:${zout} â‰ˆ ${powerRatio.toFixed(2)}:1)`;
    };
    const computeSWR = () => {
        if (!(swrInput instanceof HTMLInputElement) || !swrRl) return;
        const swr = Number(swrInput.value);
        if (!Number.isFinite(swr) || swr < 1) {
            swrRl.textContent = 'â€”';
            return;
        }
        const gamma = (swr - 1) / (swr + 1);
        const rl = -20 * Math.log10(Math.max(gamma, 1e-12));
        swrRl.textContent = `${rl.toFixed(2)} dB`;
    };
    const computeCoaxLoss = () => {
        if (!(coaxLength instanceof HTMLInputElement) || !(coaxAtten instanceof HTMLInputElement) || !coaxLoss) return;
        const len = Number(coaxLength.value);
        const att = Number(coaxAtten.value);
        if (!Number.isFinite(len) || len < 0 || !Number.isFinite(att) || att < 0) {
            coaxLoss.textContent = 'â€”';
            return;
        }
        const loss = (len / 100) * att;
        coaxLoss.textContent = `${loss.toFixed(2)} dB`;
    };

    const computeFspl = () => {
        if (!(fsplDistance instanceof HTMLInputElement) || !(fsplFrequency instanceof HTMLInputElement) || !fsplLoss) return;
        const d = Number(fsplDistance.value);
        const f = Number(fsplFrequency.value);
        if (!Number.isFinite(d) || d <= 0 || !Number.isFinite(f) || f <= 0) {
            fsplLoss.textContent = 'â€”';
            return;
        }
        const loss = 32.44 + (20 * Math.log10(d)) + (20 * Math.log10(f));
        fsplLoss.textContent = `${loss.toFixed(2)} dB`;
    };

    const computeRuntime = () => {
        if (!(runtimeCapacity instanceof HTMLInputElement) || !(runtimeCurrent instanceof HTMLInputElement) || !runtimeHours) return;
        const capacity = Number(runtimeCapacity.value);
        const current = Number(runtimeCurrent.value);
        if (!Number.isFinite(capacity) || capacity <= 0 || !Number.isFinite(current) || current <= 0) {
            runtimeHours.textContent = 'â€”';
            return;
        }
        const hours = capacity / current;
        runtimeHours.textContent = `${hours.toFixed(2)} h`;
    };

    const computeBandwidth = () => {
        if (!(bandwidthRate instanceof HTMLInputElement) || !(bandwidthRolloff instanceof HTMLInputElement) || !bandwidthResult) return;
        const rate = Number(bandwidthRate.value);
        const rolloff = Number(bandwidthRolloff.value);
        if (!Number.isFinite(rate) || rate <= 0 || !Number.isFinite(rolloff) || rolloff < 0) {
            bandwidthResult.textContent = 'â€”';
            return;
        }
        const bw = rate * (1 + rolloff);
        bandwidthResult.textContent = `${bw.toFixed(1)} Hz`;
    };
    const computeQuarterWave = () => {
        if (!(quarterWaveFrequency instanceof HTMLInputElement) || !(quarterWaveVf instanceof HTMLInputElement) || !quarterWaveLength) return;
        const f = Number(quarterWaveFrequency.value);
        const vf = Number(quarterWaveVf.value);
        if (!Number.isFinite(f) || f <= 0 || !Number.isFinite(vf) || vf <= 0 || vf > 1) {
            quarterWaveLength.textContent = 'â€”';
            return;
        }
        const meters = (71.25 / f) * vf;
        quarterWaveLength.textContent = `${meters.toFixed(2)} ${i18n.meters_unit}`;
    };

    const computeErp = () => {
        if (!(erpPower instanceof HTMLInputElement) || !(erpLoss instanceof HTMLInputElement) || !(erpGain instanceof HTMLInputElement) || !erpResult) return;
        const pwr = Number(erpPower.value);
        const loss = Number(erpLoss.value);
        const gain = Number(erpGain.value);
        if (!Number.isFinite(pwr) || pwr <= 0 || !Number.isFinite(loss) || !Number.isFinite(gain)) {
            erpResult.textContent = 'â€”';
            return;
        }
        const netDb = gain - loss;
        const erp = pwr * (10 ** (netDb / 10));
        erpResult.textContent = `${erp.toFixed(2)} W`;
    };


    const computeDipole = () => {
        if (!(dipoleFrequency instanceof HTMLInputElement) || !dipoleLength) return;
        const f = Number(dipoleFrequency.value);
        if (!Number.isFinite(f) || f <= 0) {
            dipoleLength.textContent = 'â€”';
            return;
        }
        const lengthMeters = 143 / f;
        dipoleLength.textContent = `${lengthMeters.toFixed(2)} ${i18n.meters_unit}`;
    };


    const computeDutyCycle = () => {
        if (!(dutyTx instanceof HTMLInputElement) || !(dutyPeriod instanceof HTMLInputElement) || !dutyResult) return;
        const tx = Number(dutyTx.value);
        const period = Number(dutyPeriod.value);
        if (!Number.isFinite(tx) || tx < 0 || !Number.isFinite(period) || period <= 0 || tx > period) {
            dutyResult.textContent = 'â€”';
            return;
        }
        dutyResult.textContent = `${((tx / period) * 100).toFixed(1)} %`;
    };
    const computeDivider = () => {
        if (!(dividerVin instanceof HTMLInputElement) || !(dividerR1 instanceof HTMLInputElement) || !(dividerR2 instanceof HTMLInputElement) || !dividerVout) return;
        const vin = Number(dividerVin.value);
        const r1 = Number(dividerR1.value);
        const r2 = Number(dividerR2.value);
        if (!Number.isFinite(vin) || vin < 0 || !Number.isFinite(r1) || r1 <= 0 || !Number.isFinite(r2) || r2 <= 0) {
            dividerVout.textContent = 'â€”';
            return;
        }
        const vout = vin * (r2 / (r1 + r2));
        dividerVout.textContent = `${vout.toFixed(3)} V`;
    };

    const computeMismatchLoss = () => {
        if (!(mismatchSwr instanceof HTMLInputElement) || !mismatchGamma || !mismatchLoss) return;
        const swr = Number(mismatchSwr.value);
        if (!Number.isFinite(swr) || swr < 1) {
            mismatchGamma.textContent = 'â€”';
            mismatchLoss.textContent = 'â€”';
            return;
        }
        const gamma = (swr - 1) / (swr + 1);
        const loss = -10 * Math.log10(Math.max(1 - (gamma * gamma), 1e-12));
        mismatchGamma.textContent = gamma.toFixed(4);
        mismatchLoss.textContent = `${loss.toFixed(3)} dB`;
    };

    const computeResistorCombo = () => {
        if (!(resistorTarget instanceof HTMLInputElement) || !(resistorMaxCount instanceof HTMLInputElement) || !resistorBest) return;
        const target = Number(resistorTarget.value);
        const maxCount = Math.max(1, Math.min(3, Math.round(Number(resistorMaxCount.value))));
        if (!Number.isFinite(target) || target <= 0) {
            resistorBest.textContent = 'â€”';
            return;
        }

        const series = [10, 12, 15, 18, 22, 27, 33, 39, 47, 56, 68, 82];
        const standardValues = [];
        for (let decade = 0; decade <= 6; decade++) {
            const factor = 10 ** decade;
            for (const base of series) {
                standardValues.push(base * factor);
            }
        }

        const candidates = [];
        for (const r of standardValues) {
            candidates.push({ eq: r, text: `${r.toLocaleString('fr-BE')} Î©` });
        }
        if (maxCount >= 2) {
            for (const r1 of standardValues) {
                for (const r2 of standardValues) {
                    const s = r1 + r2;
                    const p = 1 / ((1 / r1) + (1 / r2));
                    candidates.push({ eq: s, text: `${r1.toLocaleString('fr-BE')}Î© + ${r2.toLocaleString('fr-BE')}Î©` });
                    candidates.push({ eq: p, text: `${r1.toLocaleString('fr-BE')}Î© // ${r2.toLocaleString('fr-BE')}Î©` });
                }
            }
        }

        let best = null;
        for (const candidate of candidates) {
            const error = Math.abs(candidate.eq - target);
            if (best === null || error < best.error) {
                best = { ...candidate, error };
            }
        }
        if (!best) {
            resistorBest.textContent = 'â€”';
            return;
        }
        const pctError = (best.error / target) * 100;
        resistorBest.textContent = `${best.text} â‰ˆ ${best.eq.toFixed(2)} Î© (Î” ${pctError.toFixed(2)}%)`;
    };

    const computeSolarEnergy = () => {
        if (!(solarWatts instanceof HTMLInputElement) || !(solarHours instanceof HTMLInputElement) || !solarEnergy) return;
        const watts = Number(solarWatts.value);
        const hours = Number(solarHours.value);
        if (!Number.isFinite(watts) || watts < 0 || !Number.isFinite(hours) || hours < 0) {
            solarEnergy.textContent = 'â€”';
            return;
        }
        solarEnergy.textContent = `${(watts * hours).toFixed(1)} Wh`;
    };

    const computeBatteryCurrent = () => {
        if (!(batteryVoltage instanceof HTMLInputElement) || !(batteryLoad instanceof HTMLInputElement) || !batteryCurrent) return;
        const voltage = Number(batteryVoltage.value);
        const load = Number(batteryLoad.value);
        if (!Number.isFinite(voltage) || voltage <= 0 || !Number.isFinite(load) || load < 0) {
            batteryCurrent.textContent = 'â€”';
            return;
        }
        batteryCurrent.textContent = `${(load / voltage).toFixed(2)} A`;
    };


    const computeXl = () => {
        if (!(xlFreq instanceof HTMLInputElement) || !(xlInductance instanceof HTMLInputElement) || !xlResult) return;
        const f = Number(xlFreq.value);
        const lMicro = Number(xlInductance.value);
        if (!Number.isFinite(f) || f <= 0 || f > 1e6 || !Number.isFinite(lMicro) || lMicro <= 0 || lMicro > 1e6) {
            xlResult.textContent = 'â€”';
            return;
        }
        const l = lMicro * 1e-6;
        const x = 2 * Math.PI * (f * 1e6) * l;
        xlResult.textContent = formatOhms(x);
    };

    const computeXc = () => {
        if (!(xcFreq instanceof HTMLInputElement) || !(xcCapacitance instanceof HTMLInputElement) || !xcResult) return;
        const f = Number(xcFreq.value);
        const cPico = Number(xcCapacitance.value);
        if (!Number.isFinite(f) || f <= 0 || f > 1e6 || !Number.isFinite(cPico) || cPico <= 0 || cPico > 1e9) {
            xcResult.textContent = 'â€”';
            return;
        }
        const c = cPico * 1e-12;
        const x = 1 / (2 * Math.PI * (f * 1e6) * c);
        xcResult.textContent = formatOhms(x);
    };

    const computeMuf = () => {
        if (!(mufFof2 instanceof HTMLInputElement) || !(mufAngle instanceof HTMLInputElement) || !mufResult) return;
        const fof2 = Number(mufFof2.value);
        const angle = Number(mufAngle.value);
        if (!Number.isFinite(fof2) || fof2 <= 0 || !Number.isFinite(angle) || angle <= 0 || angle >= 90) {
            mufResult.textContent = 'â€”';
            return;
        }
        const radians = angle * Math.PI / 180;
        const muf = fof2 / Math.cos(radians);
        mufResult.textContent = `${muf.toFixed(2)} MHz`;
    };


    const computeEirp = () => {
        if (!(eirpErp instanceof HTMLInputElement) || !eirpResult) return;
        const erp = Number(eirpErp.value);
        if (!Number.isFinite(erp) || erp < 0) {
            eirpResult.textContent = 'â€”';
            return;
        }
        const eirp = erp * 1.64;
        eirpResult.textContent = `${eirp.toFixed(2)} W`;
    };


    const computeSkipDistance = () => {
        if (!(skipHeight instanceof HTMLInputElement) || !(skipAngle instanceof HTMLInputElement) || !skipResult) return;
        const h = Number(skipHeight.value);
        const angle = Number(skipAngle.value);
        if (!Number.isFinite(h) || h <= 0 || !Number.isFinite(angle) || angle <= 0 || angle >= 90) {
            skipResult.textContent = 'â€”';
            return;
        }
        const radians = angle * Math.PI / 180;
        const distance = 2 * h * Math.tan(radians);
        skipResult.textContent = `${distance.toFixed(1)} ${i18n.km_unit}`;
    };


    const computeDbSum = () => {
        if (!(dbsumA instanceof HTMLInputElement) || !(dbsumB instanceof HTMLInputElement) || !dbsumResult) return;
        const a = Number(dbsumA.value);
        const b = Number(dbsumB.value);
        if (!Number.isFinite(a) || !Number.isFinite(b)) {
            dbsumResult.textContent = 'â€”';
            return;
        }
        const mw = (10 ** (a / 10)) + (10 ** (b / 10));
        const dbm = 10 * Math.log10(mw);
        dbsumResult.textContent = `${dbm.toFixed(2)} dBm`;
    };

    const computeDbwFromDbm = () => {
        if (!(dbwDbm instanceof HTMLInputElement) || !(dbwDbwInput instanceof HTMLInputElement)) return;
        const dbm = Number(dbwDbm.value);
        if (!Number.isFinite(dbm)) {
            dbwDbwInput.value = '';
            return;
        }
        dbwDbwInput.value = (dbm - 30).toFixed(2);
    };

    const computeDbmFromDbw = () => {
        if (!(dbwDbwInput instanceof HTMLInputElement) || !dbwResult) return;
        const dbw = Number(dbwDbwInput.value);
        if (!Number.isFinite(dbw)) {
            dbwResult.textContent = 'â€”';
            return;
        }
        dbwResult.textContent = `${(dbw + 30).toFixed(2)} dBm`;
    };


    const computeDbuv = () => {
        if (!(dbuvDbm instanceof HTMLInputElement) || !dbuvResult) return;
        const dbm = Number(dbuvDbm.value);
        if (!Number.isFinite(dbm)) {
            dbuvResult.textContent = 'â€”';
            return;
        }
        const dbuv = dbm + 107;
        dbuvResult.textContent = `${dbuv.toFixed(2)} ${i18n.dbuv_label || 'dBÂµV'}`;
    };


    const computeGainConversion = () => {
        if (!(gainDbd instanceof HTMLInputElement) || !gainDbi) return;
        const dbd = Number(gainDbd.value);
        if (!Number.isFinite(dbd)) {
            gainDbi.textContent = 'â€”';
            return;
        }
        gainDbi.textContent = `${(dbd + 2.15).toFixed(2)} dBi`;
    };






    const computeOhmLaw = () => {
        if (!(ohmVoltage instanceof HTMLInputElement) || !(ohmCurrent instanceof HTMLInputElement) || !(ohmResistance instanceof HTMLInputElement)) return;
        const values = [Number(ohmVoltage.value), Number(ohmCurrent.value), Number(ohmResistance.value)];
        const valid = values.map((v) => Number.isFinite(v) && v > 0);
        const count = valid.filter(Boolean).length;
        if (count !== 2) return;
        if (!valid[0] && valid[1] && valid[2]) ohmVoltage.value = (values[1] * values[2]).toFixed(2);
        if (!valid[1] && valid[0] && valid[2]) ohmCurrent.value = (values[0] / values[2]).toFixed(3);
        if (!valid[2] && valid[0] && valid[1]) ohmResistance.value = (values[0] / values[1]).toFixed(2);
    };

    const computeLinkBudget = () => {
        if (!(lbPtx instanceof HTMLInputElement) || !(lbGtx instanceof HTMLInputElement) || !(lbGrx instanceof HTMLInputElement) || !(lbLoss instanceof HTMLInputElement) || !lbPrx) return;
        const ptx = Number(lbPtx.value);
        const gtx = Number(lbGtx.value);
        const grx = Number(lbGrx.value);
        const loss = Number(lbLoss.value);
        if (![ptx, gtx, grx, loss].every((v) => Number.isFinite(v))) {
            lbPrx.textContent = 'â€”';
            return;
        }
        lbPrx.textContent = `${(ptx + gtx + grx - loss).toFixed(2)} dBm`;
    };

    const toolLinks = document.querySelectorAll('[data-tool-target]');
    const toolsContent = document.getElementById('tools-content');
    const toolPanelsCache = new Map();
    const toolPanelRequests = new Map();
    const knownToolIds = new Set(['tool-grid']);
    toolLinks.forEach((link) => {
        const targetId = link.getAttribute('data-tool-target') || '';
        if (/^tool-[a-z0-9-]+$/.test(targetId)) {
            knownToolIds.add(targetId);
        }
    });
    document.querySelectorAll('[data-tool-panel]').forEach((panel) => {
        if (/^tool-[a-z0-9-]+$/.test(panel.id)) {
            knownToolIds.add(panel.id);
        }
        toolPanelsCache.set(panel.id, panel);
    });

    const getToolPanels = () => Array.from(document.querySelectorAll('[data-tool-panel]'));

    const loadToolPanel = async (id) => {
        if (!/^tool-[a-z0-9-]+$/.test(id)) {
            return null;
        }
        if (!knownToolIds.has(id)) {
            return null;
        }
        if (toolPanelsCache.has(id)) {
            return toolPanelsCache.get(id) ?? null;
        }
        if (toolPanelRequests.has(id)) {
            return toolPanelRequests.get(id) ?? null;
        }
        if (!(toolsContent instanceof HTMLElement)) {
            return null;
        }

        const endpoint = new URL(window.location.href);
        endpoint.hash = '';
        endpoint.searchParams.set('route', 'tools');
        endpoint.searchParams.set('ajax', 'tool_panel');
        endpoint.searchParams.set('id', id);

        const request = fetch(endpoint.toString(), {
            headers: { 'Accept': 'text/html' },
            credentials: 'same-origin',
        }).then(async (response) => {
            if (!response.ok) {
                return null;
            }

            const html = await response.text();
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const panel = wrapper.querySelector(`#${id}[data-tool-panel]`) || wrapper.querySelector(`[data-tool-panel]#${id}`) || wrapper.firstElementChild;
            if (!(panel instanceof HTMLElement) || panel.id !== id || panel.getAttribute('data-tool-panel') === null) {
                return null;
            }

            panel.classList.add('is-hidden');
            toolsContent.appendChild(panel);
            refreshDomRefs();
            toolPanelsCache.set(id, panel);
            return panel;
        }).finally(() => {
            toolPanelRequests.delete(id);
        });

        toolPanelRequests.set(id, request);
        return request;
    };

    let activeToolRequestToken = 0;

    const setActiveTool = async (requestedId) => {
        const requestToken = ++activeToolRequestToken;
        let id = requestedId;
        if (!id) {
            id = 'tool-grid';
        }
        if (!knownToolIds.has(id) && /^tool-[a-z0-9-]+$/.test(id)) {
            knownToolIds.add(id);
        }
        if (!knownToolIds.has(id)) {
            id = 'tool-grid';
        }

        let activePanel = null;
        try {
            activePanel = await loadToolPanel(id);
        } catch (_) {
            activePanel = null;
        }
        if (activePanel === null && id !== 'tool-grid') {
            id = 'tool-grid';
            try {
                activePanel = await loadToolPanel(id);
            } catch (_) {
                activePanel = null;
            }
        }
        if (requestToken !== activeToolRequestToken) {
            return;
        }
        if (activePanel === null) {
            setError(i18n.err_tool_load);
            return;
        }

        clearError();
        initToolIfNeeded(id);
        getToolPanels().forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.id !== id);
        });
        toolLinks.forEach((link) => {
            const isActive = link.getAttribute('data-tool-target') === id;
            link.classList.toggle('is-active', isActive);
        });
    };
    const embeddedPanels = getToolPanels();
    const initialTool = window.location.hash
        ? window.location.hash.slice(1)
        : (embeddedPanels.length === 1 ? embeddedPanels[0].id : 'tool-grid');
    setActiveTool(initialTool);
    const resolveToolTarget = (eventTarget) => {
        const baseElement = eventTarget instanceof Element
            ? eventTarget
            : (eventTarget instanceof Node ? eventTarget.parentElement : null);
        if (!(baseElement instanceof Element)) {
            return '';
        }

        const trigger = baseElement.closest('[data-tool-target], a[href^="#tool-"]');
        if (!(trigger instanceof Element)) {
            return '';
        }

        const datasetTarget = (trigger.getAttribute('data-tool-target') || '').trim();
        if (/^tool-[a-z0-9-]+$/.test(datasetTarget)) {
            return datasetTarget;
        }

        if (!(trigger instanceof HTMLAnchorElement)) {
            return '';
        }

        const href = (trigger.getAttribute('href') || '').trim();
        const hrefTarget = href.startsWith('#') ? href.slice(1) : '';
        if (/^tool-[a-z0-9-]+$/.test(hrefTarget)) {
            return hrefTarget;
        }

        return '';
    };

    document.addEventListener('click', (event) => {
        const targetId = resolveToolTarget(event.target);
        if (targetId === '') {
            return;
        }

        event.preventDefault();
        window.history.replaceState(null, '', `#${targetId}`);
        setActiveTool(targetId);
    });
    window.addEventListener('hashchange', () => {
        const hashTool = window.location.hash ? window.location.hash.slice(1) : 'tool-grid';
        setActiveTool(hashTool);
    });


})();

