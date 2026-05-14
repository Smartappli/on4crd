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
        'tool-mismatch': () => {
            mismatchSwr?.addEventListener('input', computeMismatchLoss);
            computeMismatchLoss();
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
    };
