
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
                output.textContent = '—';
                return;
            }
            const y = convert(x);
            output.textContent = Number.isFinite(y) ? y.toFixed(6).replace(/\.?(0+)$/, '') : '—';
        };
        input.addEventListener('input', sync);
        sync();
    };

    const initUnitConversions = () => {
        const panel = document.getElementById('tool-unit-conversions');
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
                const watts = Number(wattsInput.value);
                if (!Number.isFinite(watts) || watts <= 0) {
                    dbmOut.textContent = '—';
                    return;
                }
                const dbm = 10 * Math.log10(watts * 1000);
                dbmOut.textContent = dbm.toFixed(2);
            });

            dbmInput?.addEventListener('input', () => {
                if (!(dbmInput instanceof HTMLInputElement) || !wattsOut) return;
                const dbm = Number(dbmInput.value);
                if (!Number.isFinite(dbm)) {
                    wattsOut.textContent = '—';
                    return;
                }
                const watts = Math.pow(10, dbm / 10) / 1000;
                wattsOut.textContent = `${watts.toFixed(4)} ${i18n.watts_out_label || 'W'}`;
            });
        },
        'tool-unit-conversions': initUnitConversions,
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
