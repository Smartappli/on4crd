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
    let foundCqZone = null;
    let foundItuZone = null;
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
    let swrForward = null;
    let swrReflected = null;
    let swrValue = null;
    let swrReturnLoss = null;
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
    let ohmEditedFields = [];
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
        foundCqZone = document.getElementById('grid-found-cq-zone');
        foundItuZone = document.getElementById('grid-found-itu-zone');
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
        swrForward = document.getElementById('swr-forward');
        swrReflected = document.getElementById('swr-reflected');
        swrValue = document.getElementById('swr-value');
        swrReturnLoss = document.getElementById('swr-return-loss');
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

    const readNumberInput = (input) => {
        const normalized = String(input?.value || '').trim().replace(/\s+/g, '').replace(/,/g, '.');
        if (normalized === '' || !/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i.test(normalized)) {
            return NaN;
        }

        const value = Number(normalized);
        if (!Number.isFinite(value)) {
            return NaN;
        }

        const min = input?.dataset?.min !== undefined ? Number(input.dataset.min) : NaN;
        const max = input?.dataset?.max !== undefined ? Number(input.dataset.max) : NaN;
        if ((Number.isFinite(min) && value < min) || (Number.isFinite(max) && value > max)) {
            return NaN;
        }

        return value;
    };

    const trimTrailingDecimalZeros = (value) => {
        let text = String(value);
        if (!text.includes('.')) {
            return text;
        }
        while (text.endsWith('0')) {
            text = text.slice(0, -1);
        }
        return text.endsWith('.') ? text.slice(0, -1) : text;
    };

    const trimExponentialDecimalZeros = (value) => {
        const [mantissa, exponent] = String(value).split('e');
        if (typeof exponent !== 'string') {
            return trimTrailingDecimalZeros(value);
        }
        return `${trimTrailingDecimalZeros(mantissa)}e${exponent}`;
    };

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
            const x = readNumberInput(input);
            if (!Number.isFinite(x)) {
                output.textContent = '—';
                return;
            }
            const y = convert(x);
            output.textContent = Number.isFinite(y) ? trimTrailingDecimalZeros(y.toFixed(6)) : '—';
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
            if (!Number.isFinite(value)) return '—';
            const abs = Math.abs(value);
            if (abs !== 0 && (abs >= 1e7 || abs < 1e-4)) {
                return trimExponentialDecimalZeros(value.toExponential(6));
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
            const value = readNumberInput(input);
            if (!from || !to || !Number.isFinite(value)) {
                output.textContent = '—';
                if (reference instanceof HTMLElement) reference.textContent = '—';
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
                    const locator = payload.locator || toMaidenhead(lat, lon, 6);
                    if (foundAddress) foundAddress.textContent = payload.display_name || query;
                    if (foundCoords) foundCoords.textContent = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
                    if (foundLocator) foundLocator.textContent = locator;
                    if (foundCqZone) foundCqZone.textContent = payload.cq_zone || '-';
                    if (foundItuZone) foundItuZone.textContent = payload.itu_zone || '-';
                    result?.classList.remove('is-hidden');
                } catch (error) {
                    setError(error instanceof Error ? error.message : i18n.err_grid_calc);
                }
            });
        },
        'tool-freq-wave': () => {
            freqInput?.addEventListener('input', () => {
                if (!(freqInput instanceof HTMLInputElement) || !freqOut) return;
                const mhz = readNumberInput(freqInput);
                if (!Number.isFinite(mhz) || mhz <= 0) {
                    freqOut.textContent = '—';
                    return;
                }
                const meters = 299.792458 / mhz;
                freqOut.textContent = `${meters.toFixed(3)} ${i18n.meters_unit}`;
            });
        },
        'tool-power': () => {
            wattsInput?.addEventListener('input', () => {
                if (!(wattsInput instanceof HTMLInputElement) || !dbmOut) return;
                const watts = readNumberInput(wattsInput);
                if (!Number.isFinite(watts) || watts <= 0) {
                    dbmOut.textContent = '—';
                    return;
                }
                const dbm = 10 * Math.log10(watts * 1000);
                dbmOut.textContent = dbm.toFixed(2);
            });

            dbmInput?.addEventListener('input', () => {
                if (!(dbmInput instanceof HTMLInputElement) || !wattsOut) return;
                const dbm = readNumberInput(dbmInput);
                if (!Number.isFinite(dbm)) {
                    wattsOut.textContent = '—';
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
            swrForward?.addEventListener('input', computeSWR);
            swrReflected?.addEventListener('input', computeSWR);
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
                    locatorDistance.textContent = '—';
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
            ohmVoltage?.addEventListener('input', () => computeOhmLaw('voltage'));
            ohmCurrent?.addEventListener('input', () => computeOhmLaw('current'));
            ohmResistance?.addEventListener('input', () => computeOhmLaw('resistance'));
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
            return '—';
        }
        if (value >= 1000) {
            return `${(value / 1000).toFixed(3)} kΩ`;
        }
        return `${value.toFixed(2)} Ω`;
    };

    const computeFilter = () => {
        if (!(filterFreq instanceof HTMLInputElement) || !(filterImpedance instanceof HTMLInputElement) || !filterL || !filterC) return;
        const fMHz = readNumberInput(filterFreq);
        const z = readNumberInput(filterImpedance);
        if (!Number.isFinite(fMHz) || fMHz <= 0 || !Number.isFinite(z) || z <= 0) {
            filterL.textContent = '—';
            filterC.textContent = '—';
            return;
        }
        const f = fMHz * 1e6;
        const lHenrys = z / (2 * Math.PI * f);
        const cFarads = 1 / (2 * Math.PI * f * z);
        filterL.textContent = `${(lHenrys * 1e6).toFixed(3)} µH`;
        filterC.textContent = `${(cFarads * 1e12).toFixed(2)} pF`;
    };
    const computeBalun = () => {
        if (!(balunSource instanceof HTMLInputElement) || !(balunLoad instanceof HTMLInputElement) || !balunRatio) return;
        const zin = readNumberInput(balunSource);
        const zout = readNumberInput(balunLoad);
        if (!Number.isFinite(zin) || zin <= 0 || !Number.isFinite(zout) || zout <= 0) {
            balunRatio.textContent = '—';
            return;
        }
        const ratio = Math.sqrt(zout / zin);
        const powerRatio = zout / zin;
        balunRatio.textContent = `1:${ratio.toFixed(2)} (Z ${zin}:${zout} ≈ ${powerRatio.toFixed(2)}:1)`;
    };
    const computeSWR = () => {
        if (!(swrForward instanceof HTMLInputElement) || !(swrReflected instanceof HTMLInputElement) || !swrValue) return;
        const forward = readNumberInput(swrForward);
        const reflected = readNumberInput(swrReflected);
        if (!Number.isFinite(forward) || forward <= 0 || !Number.isFinite(reflected) || reflected < 0 || reflected >= forward) {
            swrValue.textContent = '—';
            if (swrReturnLoss) swrReturnLoss.textContent = '—';
            return;
        }

        const gamma = Math.sqrt(reflected / forward);
        const swr = (1 + gamma) / (1 - gamma);
        swrValue.textContent = swr.toFixed(2);
        if (swrReturnLoss) {
            swrReturnLoss.textContent = gamma === 0 ? '∞ dB' : `${(-20 * Math.log10(gamma)).toFixed(2)} dB`;
        }
    };
    const computeCoaxLoss = () => {
        if (!(coaxLength instanceof HTMLInputElement) || !(coaxAtten instanceof HTMLInputElement) || !coaxLoss) return;
        const len = readNumberInput(coaxLength);
        const att = readNumberInput(coaxAtten);
        if (!Number.isFinite(len) || len < 0 || !Number.isFinite(att) || att < 0) {
            coaxLoss.textContent = '—';
            return;
        }
        const loss = (len / 100) * att;
        coaxLoss.textContent = `${loss.toFixed(2)} dB`;
    };

    const computeFspl = () => {
        if (!(fsplDistance instanceof HTMLInputElement) || !(fsplFrequency instanceof HTMLInputElement) || !fsplLoss) return;
        const d = readNumberInput(fsplDistance);
        const f = readNumberInput(fsplFrequency);
        if (!Number.isFinite(d) || d <= 0 || !Number.isFinite(f) || f <= 0) {
            fsplLoss.textContent = '—';
            return;
        }
        const loss = 32.44 + (20 * Math.log10(d)) + (20 * Math.log10(f));
        fsplLoss.textContent = `${loss.toFixed(2)} dB`;
    };

    const computeRuntime = () => {
        if (!(runtimeCapacity instanceof HTMLInputElement) || !(runtimeCurrent instanceof HTMLInputElement) || !runtimeHours) return;
        const capacity = readNumberInput(runtimeCapacity);
        const current = readNumberInput(runtimeCurrent);
        if (!Number.isFinite(capacity) || capacity <= 0 || !Number.isFinite(current) || current <= 0) {
            runtimeHours.textContent = '—';
            return;
        }
        const hours = capacity / current;
        runtimeHours.textContent = `${hours.toFixed(2)} h`;
    };

    const computeBandwidth = () => {
        if (!(bandwidthRate instanceof HTMLInputElement) || !(bandwidthRolloff instanceof HTMLInputElement) || !bandwidthResult) return;
        const rate = readNumberInput(bandwidthRate);
        const rolloff = readNumberInput(bandwidthRolloff);
        if (!Number.isFinite(rate) || rate <= 0 || !Number.isFinite(rolloff) || rolloff < 0) {
            bandwidthResult.textContent = '—';
            return;
        }
        const bw = rate * (1 + rolloff);
        bandwidthResult.textContent = `${bw.toFixed(1)} Hz`;
    };
    const computeQuarterWave = () => {
        if (!(quarterWaveFrequency instanceof HTMLInputElement) || !(quarterWaveVf instanceof HTMLInputElement) || !quarterWaveLength) return;
        const f = readNumberInput(quarterWaveFrequency);
        const vf = readNumberInput(quarterWaveVf);
        if (!Number.isFinite(f) || f <= 0 || !Number.isFinite(vf) || vf <= 0 || vf > 1) {
            quarterWaveLength.textContent = '—';
            return;
        }
        const meters = (71.25 / f) * vf;
        quarterWaveLength.textContent = `${meters.toFixed(2)} ${i18n.meters_unit}`;
    };

    const computeErp = () => {
        if (!(erpPower instanceof HTMLInputElement) || !(erpLoss instanceof HTMLInputElement) || !(erpGain instanceof HTMLInputElement) || !erpResult) return;
        const pwr = readNumberInput(erpPower);
        const loss = readNumberInput(erpLoss);
        const gain = readNumberInput(erpGain);
        if (!Number.isFinite(pwr) || pwr <= 0 || !Number.isFinite(loss) || !Number.isFinite(gain)) {
            erpResult.textContent = '—';
            return;
        }
        const netDb = gain - loss;
        const erp = pwr * (10 ** (netDb / 10));
        erpResult.textContent = `${erp.toFixed(2)} W`;
    };


    const computeDipole = () => {
        if (!(dipoleFrequency instanceof HTMLInputElement) || !dipoleLength) return;
        const f = readNumberInput(dipoleFrequency);
        if (!Number.isFinite(f) || f <= 0) {
            dipoleLength.textContent = '—';
            return;
        }
        const lengthMeters = 143 / f;
        dipoleLength.textContent = `${lengthMeters.toFixed(2)} ${i18n.meters_unit}`;
    };


    const computeDutyCycle = () => {
        if (!(dutyTx instanceof HTMLInputElement) || !(dutyPeriod instanceof HTMLInputElement) || !dutyResult) return;
        const tx = readNumberInput(dutyTx);
        const period = readNumberInput(dutyPeriod);
        if (!Number.isFinite(tx) || tx < 0 || !Number.isFinite(period) || period <= 0 || tx > period) {
            dutyResult.textContent = '—';
            return;
        }
        dutyResult.textContent = `${((tx / period) * 100).toFixed(1)} %`;
    };
    const computeDivider = () => {
        if (!(dividerVin instanceof HTMLInputElement) || !(dividerR1 instanceof HTMLInputElement) || !(dividerR2 instanceof HTMLInputElement) || !dividerVout) return;
        const vin = readNumberInput(dividerVin);
        const r1 = readNumberInput(dividerR1);
        const r2 = readNumberInput(dividerR2);
        if (!Number.isFinite(vin) || vin < 0 || !Number.isFinite(r1) || r1 <= 0 || !Number.isFinite(r2) || r2 <= 0) {
            dividerVout.textContent = '—';
            return;
        }
        const vout = vin * (r2 / (r1 + r2));
        dividerVout.textContent = `${vout.toFixed(3)} V`;
    };

    const computeMismatchLoss = () => {
        if (!(mismatchSwr instanceof HTMLInputElement) || !mismatchGamma || !mismatchLoss) return;
        const swr = readNumberInput(mismatchSwr);
        if (!Number.isFinite(swr) || swr < 1) {
            mismatchGamma.textContent = '—';
            mismatchLoss.textContent = '—';
            return;
        }
        const gamma = (swr - 1) / (swr + 1);
        const loss = -10 * Math.log10(Math.max(1 - (gamma * gamma), 1e-12));
        mismatchGamma.textContent = gamma.toFixed(4);
        mismatchLoss.textContent = `${loss.toFixed(3)} dB`;
    };

    const computeResistorCombo = () => {
        if (!(resistorTarget instanceof HTMLInputElement) || !(resistorMaxCount instanceof HTMLInputElement) || !resistorBest) return;
        const target = readNumberInput(resistorTarget);
        const maxCount = Math.max(1, Math.min(3, Math.round(readNumberInput(resistorMaxCount))));
        if (!Number.isFinite(target) || target <= 0) {
            resistorBest.textContent = '—';
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
            candidates.push({ eq: r, text: `${r.toLocaleString('fr-BE')} Ω` });
        }
        if (maxCount >= 2) {
            for (const r1 of standardValues) {
                for (const r2 of standardValues) {
                    const s = r1 + r2;
                    const p = 1 / ((1 / r1) + (1 / r2));
                    candidates.push({ eq: s, text: `${r1.toLocaleString('fr-BE')}Ω + ${r2.toLocaleString('fr-BE')}Ω` });
                    candidates.push({ eq: p, text: `${r1.toLocaleString('fr-BE')}Ω // ${r2.toLocaleString('fr-BE')}Ω` });
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
            resistorBest.textContent = '—';
            return;
        }
        const pctError = (best.error / target) * 100;
        resistorBest.textContent = `${best.text} ≈ ${best.eq.toFixed(2)} Ω (Δ ${pctError.toFixed(2)}%)`;
    };

    const computeSolarEnergy = () => {
        if (!(solarWatts instanceof HTMLInputElement) || !(solarHours instanceof HTMLInputElement) || !solarEnergy) return;
        const watts = readNumberInput(solarWatts);
        const hours = readNumberInput(solarHours);
        if (!Number.isFinite(watts) || watts < 0 || !Number.isFinite(hours) || hours < 0) {
            solarEnergy.textContent = '—';
            return;
        }
        solarEnergy.textContent = `${(watts * hours).toFixed(1)} Wh`;
    };

    const computeBatteryCurrent = () => {
        if (!(batteryVoltage instanceof HTMLInputElement) || !(batteryLoad instanceof HTMLInputElement) || !batteryCurrent) return;
        const voltage = readNumberInput(batteryVoltage);
        const load = readNumberInput(batteryLoad);
        if (!Number.isFinite(voltage) || voltage <= 0 || !Number.isFinite(load) || load < 0) {
            batteryCurrent.textContent = '—';
            return;
        }
        batteryCurrent.textContent = `${(load / voltage).toFixed(2)} A`;
    };


    const computeXl = () => {
        if (!(xlFreq instanceof HTMLInputElement) || !(xlInductance instanceof HTMLInputElement) || !xlResult) return;
        const f = readNumberInput(xlFreq);
        const lMicro = readNumberInput(xlInductance);
        if (!Number.isFinite(f) || f <= 0 || f > 1e6 || !Number.isFinite(lMicro) || lMicro <= 0 || lMicro > 1e6) {
            xlResult.textContent = '—';
            return;
        }
        const l = lMicro * 1e-6;
        const x = 2 * Math.PI * (f * 1e6) * l;
        xlResult.textContent = formatOhms(x);
    };

    const computeXc = () => {
        if (!(xcFreq instanceof HTMLInputElement) || !(xcCapacitance instanceof HTMLInputElement) || !xcResult) return;
        const f = readNumberInput(xcFreq);
        const cPico = readNumberInput(xcCapacitance);
        if (!Number.isFinite(f) || f <= 0 || f > 1e6 || !Number.isFinite(cPico) || cPico <= 0 || cPico > 1e9) {
            xcResult.textContent = '—';
            return;
        }
        const c = cPico * 1e-12;
        const x = 1 / (2 * Math.PI * (f * 1e6) * c);
        xcResult.textContent = formatOhms(x);
    };

    const computeMuf = () => {
        if (!(mufFof2 instanceof HTMLInputElement) || !(mufAngle instanceof HTMLInputElement) || !mufResult) return;
        const fof2 = readNumberInput(mufFof2);
        const angle = readNumberInput(mufAngle);
        if (!Number.isFinite(fof2) || fof2 <= 0 || !Number.isFinite(angle) || angle <= 0 || angle >= 90) {
            mufResult.textContent = '—';
            return;
        }
        const radians = angle * Math.PI / 180;
        const muf = fof2 / Math.cos(radians);
        mufResult.textContent = `${muf.toFixed(2)} MHz`;
    };


    const computeEirp = () => {
        if (!(eirpErp instanceof HTMLInputElement) || !eirpResult) return;
        const erp = readNumberInput(eirpErp);
        if (!Number.isFinite(erp) || erp < 0) {
            eirpResult.textContent = '—';
            return;
        }
        const eirp = erp * 1.64;
        eirpResult.textContent = `${eirp.toFixed(2)} W`;
    };


    const computeSkipDistance = () => {
        if (!(skipHeight instanceof HTMLInputElement) || !(skipAngle instanceof HTMLInputElement) || !skipResult) return;
        const h = readNumberInput(skipHeight);
        const angle = readNumberInput(skipAngle);
        if (!Number.isFinite(h) || h <= 0 || !Number.isFinite(angle) || angle <= 0 || angle >= 90) {
            skipResult.textContent = '—';
            return;
        }
        const radians = angle * Math.PI / 180;
        const distance = 2 * h * Math.tan(radians);
        skipResult.textContent = `${distance.toFixed(1)} ${i18n.km_unit}`;
    };


    const computeDbSum = () => {
        if (!(dbsumA instanceof HTMLInputElement) || !(dbsumB instanceof HTMLInputElement) || !dbsumResult) return;
        const a = readNumberInput(dbsumA);
        const b = readNumberInput(dbsumB);
        if (!Number.isFinite(a) || !Number.isFinite(b)) {
            dbsumResult.textContent = '—';
            return;
        }
        const mw = (10 ** (a / 10)) + (10 ** (b / 10));
        const dbm = 10 * Math.log10(mw);
        dbsumResult.textContent = `${dbm.toFixed(2)} dBm`;
    };

    const computeDbwFromDbm = () => {
        if (!(dbwDbm instanceof HTMLInputElement) || !(dbwDbwInput instanceof HTMLInputElement) || !dbwResult) return;
        const dbm = readNumberInput(dbwDbm);
        if (!Number.isFinite(dbm)) {
            dbwDbwInput.value = '';
            dbwResult.textContent = '—';
            return;
        }
        dbwDbwInput.value = (dbm - 30).toFixed(2);
        dbwResult.textContent = `${dbm.toFixed(2)} dBm`;
    };

    const computeDbmFromDbw = () => {
        if (!(dbwDbwInput instanceof HTMLInputElement) || !(dbwDbm instanceof HTMLInputElement) || !dbwResult) return;
        const dbw = readNumberInput(dbwDbwInput);
        if (!Number.isFinite(dbw)) {
            dbwResult.textContent = '—';
            return;
        }
        const dbm = dbw + 30;
        dbwDbm.value = dbm.toFixed(2);
        dbwResult.textContent = `${dbm.toFixed(2)} dBm`;
    };


    const computeDbuv = () => {
        if (!(dbuvDbm instanceof HTMLInputElement) || !dbuvResult) return;
        const dbm = readNumberInput(dbuvDbm);
        if (!Number.isFinite(dbm)) {
            dbuvResult.textContent = '—';
            return;
        }
        const dbuv = dbm + 107;
        dbuvResult.textContent = `${dbuv.toFixed(2)} ${i18n.dbuv_label || 'dBµV'}`;
    };


    const computeGainConversion = () => {
        if (!(gainDbd instanceof HTMLInputElement) || !gainDbi) return;
        const dbd = readNumberInput(gainDbd);
        if (!Number.isFinite(dbd)) {
            gainDbi.textContent = '—';
            return;
        }
        gainDbi.textContent = `${(dbd + 2.15).toFixed(2)} dBi`;
    };






    const parseOhmDecimal = (input) => {
        const normalized = String(input.value || '').trim().replace(/\s+/g, '').replace(/,/g, '.');
        if (normalized === '' || !/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/.test(normalized)) {
            return NaN;
        }

        const value = Number(normalized);
        return Number.isFinite(value) ? value : NaN;
    };

    const formatOhmDecimal = (value) => Number(value).toFixed(2);

    const computeOhmLaw = (changedField = '') => {
        if (!(ohmVoltage instanceof HTMLInputElement) || !(ohmCurrent instanceof HTMLInputElement) || !(ohmResistance instanceof HTMLInputElement)) return;
        const fieldNames = ['voltage', 'current', 'resistance'];
        const fields = {
            voltage: ohmVoltage,
            current: ohmCurrent,
            resistance: ohmResistance,
        };
        const values = Object.fromEntries(fieldNames.map((name) => [name, parseOhmDecimal(fields[name])]));
        const validFields = fieldNames.filter((name) => Number.isFinite(values[name]) && values[name] > 0);

        if (fieldNames.includes(changedField)) {
            ohmEditedFields = ohmEditedFields.filter((name) => name !== changedField);
            if (validFields.includes(changedField)) {
                ohmEditedFields.push(changedField);
            }
        }

        if (validFields.length < 2) {
            return;
        }

        let targetField = '';
        if (validFields.length === 2) {
            targetField = fieldNames.find((name) => !validFields.includes(name)) || '';
        } else {
            const recentSources = ohmEditedFields.filter((name) => validFields.includes(name)).slice(-2);
            if (recentSources.length >= 2 && recentSources.includes(changedField)) {
                targetField = fieldNames.find((name) => !recentSources.includes(name)) || '';
            } else if (changedField === 'voltage') {
                targetField = 'current';
            } else if (changedField === 'current') {
                targetField = 'voltage';
            } else if (changedField === 'resistance') {
                targetField = 'current';
            }
        }

        if (!targetField) {
            return;
        }

        const computed = {
            voltage: () => values.current * values.resistance,
            current: () => values.resistance > 0 ? values.voltage / values.resistance : NaN,
            resistance: () => values.current > 0 ? values.voltage / values.current : NaN,
        }[targetField]();

        if (!Number.isFinite(computed) || computed <= 0) {
            return;
        }

        fields[targetField].value = formatOhmDecimal(computed);
        ohmEditedFields = ohmEditedFields.filter((name) => name !== targetField);
    };

    const computeLinkBudget = () => {
        if (!(lbPtx instanceof HTMLInputElement) || !(lbGtx instanceof HTMLInputElement) || !(lbGrx instanceof HTMLInputElement) || !(lbLoss instanceof HTMLInputElement) || !lbPrx) return;
        const ptx = readNumberInput(lbPtx);
        const gtx = readNumberInput(lbGtx);
        const grx = readNumberInput(lbGrx);
        const loss = readNumberInput(lbLoss);
        if (![ptx, gtx, grx, loss].every((v) => Number.isFinite(v))) {
            lbPrx.textContent = '—';
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

    const sanitizeToolPanelFragment = (root) => {
        root.querySelectorAll('script, iframe, object, embed, link[rel="import"], meta[http-equiv="refresh"]').forEach((node) => {
            node.remove();
        });
        root.querySelectorAll('*').forEach((node) => {
            Array.from(node.attributes).forEach((attribute) => {
                const name = attribute.name.toLowerCase();
                const value = attribute.value.trim().toLowerCase();
                if (
                    name.startsWith('on')
                    || name === 'srcdoc'
                    || ((name === 'href' || name === 'src' || name === 'xlink:href') && value.startsWith('javascript:'))
                ) {
                    node.removeAttribute(attribute.name);
                }
            });
        });
    };

    const parseToolPanelHtml = (html, id) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        sanitizeToolPanelFragment(parsed);
        const panel = parsed.getElementById(id);
        if (!(panel instanceof HTMLElement) || panel.getAttribute('data-tool-panel') === null) {
            return null;
        }

        return document.importNode(panel, true);
    };

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

        const panelEndpointUrl = toolsContent.getAttribute('data-tool-panel-url') || '/index.php?route=tools';
        const endpoint = new URL(panelEndpointUrl, window.location.origin);
        if (endpoint.origin !== window.location.origin) {
            return null;
        }
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
            const panel = parseToolPanelHtml(html, id);
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

    const setActiveTool = async (requestedId, options = {}) => {
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

        if (options.pushHistory === true && window.location.hash.slice(1) !== id) {
            window.history.pushState(null, '', `#${id}`);
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
        setActiveTool(targetId, { pushHistory: true });
    });
    const syncToolFromLocation = () => {
        const hashTool = window.location.hash ? window.location.hash.slice(1) : 'tool-grid';
        setActiveTool(hashTool);
    };
    window.addEventListener('hashchange', syncToolFromLocation);
    window.addEventListener('popstate', syncToolFromLocation);


})();

