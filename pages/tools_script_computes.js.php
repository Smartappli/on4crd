    const computeFilter = () => {
        if (!(filterFreq instanceof HTMLInputElement) || !(filterImpedance instanceof HTMLInputElement) || !filterL || !filterC) return;
        const fMHz = Number(filterFreq.value);
        const z = Number(filterImpedance.value);
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
        const zin = Number(balunSource.value);
        const zout = Number(balunLoad.value);
        if (!Number.isFinite(zin) || zin <= 0 || !Number.isFinite(zout) || zout <= 0) {
            balunRatio.textContent = '—';
            return;
        }
        const ratio = Math.sqrt(zout / zin);
        const powerRatio = zout / zin;
        balunRatio.textContent = `1:${ratio.toFixed(2)} (Z ${zin}:${zout} ≈ ${powerRatio.toFixed(2)}:1)`;
    };
    const computeSWR = () => {
        if (!(swrInput instanceof HTMLInputElement) || !swrRl) return;
        const swr = Number(swrInput.value);
        if (!Number.isFinite(swr) || swr < 1) {
            swrRl.textContent = '—';
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
            coaxLoss.textContent = '—';
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
            fsplLoss.textContent = '—';
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
            runtimeHours.textContent = '—';
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
            bandwidthResult.textContent = '—';
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
            quarterWaveLength.textContent = '—';
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
            erpResult.textContent = '—';
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
            dipoleLength.textContent = '—';
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
            dutyResult.textContent = '—';
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
            dividerVout.textContent = '—';
            return;
        }
        const vout = vin * (r2 / (r1 + r2));
        dividerVout.textContent = `${vout.toFixed(3)} V`;
    };

    const computeMismatchLoss = () => {
        if (!(mismatchSwr instanceof HTMLInputElement) || !mismatchGamma || !mismatchLoss) return;
        const swr = Number(mismatchSwr.value);
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

    const computeSolarEnergy = () => {
        if (!(solarWatts instanceof HTMLInputElement) || !(solarHours instanceof HTMLInputElement) || !solarEnergy) return;
        const watts = Number(solarWatts.value);
        const hours = Number(solarHours.value);
        if (!Number.isFinite(watts) || watts < 0 || !Number.isFinite(hours) || hours < 0) {
            solarEnergy.textContent = '—';
            return;
        }
        solarEnergy.textContent = `${(watts * hours).toFixed(1)} Wh`;
    };

    const computeBatteryCurrent = () => {
        if (!(batteryVoltage instanceof HTMLInputElement) || !(batteryLoad instanceof HTMLInputElement) || !batteryCurrent) return;
        const voltage = Number(batteryVoltage.value);
        const load = Number(batteryLoad.value);
        if (!Number.isFinite(voltage) || voltage <= 0 || !Number.isFinite(load) || load < 0) {
            batteryCurrent.textContent = '—';
            return;
        }
        batteryCurrent.textContent = `${(load / voltage).toFixed(2)} A`;
    };

    const computeMuf = () => {
        if (!(mufFof2 instanceof HTMLInputElement) || !(mufAngle instanceof HTMLInputElement) || !mufResult) return;
        const fof2 = Number(mufFof2.value);
        const angle = Number(mufAngle.value);
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
        const erp = Number(eirpErp.value);
        if (!Number.isFinite(erp) || erp < 0) {
            eirpResult.textContent = '—';
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
            skipResult.textContent = '—';
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
            dbsumResult.textContent = '—';
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
            dbwResult.textContent = '—';
            return;
        }
        dbwResult.textContent = `${(dbw + 30).toFixed(2)} dBm`;
    };


    const computeDbuv = () => {
        if (!(dbuvDbm instanceof HTMLInputElement) || !dbuvResult) return;
        const dbm = Number(dbuvDbm.value);
        if (!Number.isFinite(dbm)) {
            dbuvResult.textContent = '—';
            return;
        }
        const dbuv = dbm + 107;
        dbuvResult.textContent = `${dbuv.toFixed(2)} ${i18n.dbuv_label || 'dBµV'}`;
    };


    const computeGainConversion = () => {
        if (!(gainDbd instanceof HTMLInputElement) || !gainDbi) return;
        const dbd = Number(gainDbd.value);
        if (!Number.isFinite(dbd)) {
            gainDbi.textContent = '—';
            return;
        }
        gainDbi.textContent = `${(dbd + 2.15).toFixed(2)} dBi`;
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
