import { test, expect, type Page } from '@playwright/test';

type ToolStep =
  | { action: 'fill'; selector: string; value: string }
  | { action: 'select'; selector: string; value: string }
  | { action: 'expectVisible'; selector: string }
  | { action: 'expectValue'; selector: string; text: string }
  | { action: 'expectText'; selector: string; text: string }
  | { action: 'expectText'; selector: string; pattern: RegExp }
  | { action: 'expectContains'; selector: string; text: string };

type ToolScenario = {
  id: string;
  steps: ToolStep[];
};

const unitConversionSteps = (toolId: string): ToolScenario => ({
  id: toolId,
  steps: [
    { action: 'select', selector: '#unit-conv-group', value: 'rf' },
    { action: 'select', selector: '#unit-conv-from', value: 'mhz' },
    { action: 'select', selector: '#unit-conv-to', value: 'khz' },
    { action: 'fill', selector: '#unit-conv-input', value: '145.5' },
    { action: 'expectContains', selector: '#unit-conv-output', text: 'kHz' },
    { action: 'expectText', selector: '#unit-conv-output', pattern: /145[,\s.]?500.*kHz/ },
  ],
});

const simpleConverterScenario = (
  id: string,
  input: string,
  expected: string | RegExp,
): ToolScenario => ({
  id,
  steps: [
    { action: 'fill', selector: `#${id}-in`, value: input },
    typeof expected === 'string'
      ? { action: 'expectText', selector: `#${id}-out`, text: expected }
      : { action: 'expectText', selector: `#${id}-out`, pattern: expected },
  ],
});

const scenarios: ToolScenario[] = [
  {
    id: 'tool-grid',
    steps: [
      { action: 'expectVisible', selector: '#grid-tool-form' },
      { action: 'expectVisible', selector: '#grid-address' },
    ],
  },
  {
    id: 'tool-distance',
    steps: [
      { action: 'fill', selector: '#locator-a', value: 'JO20LI' },
      { action: 'fill', selector: '#locator-b', value: 'JN18EU' },
      { action: 'expectText', selector: '#locator-distance', pattern: /\d+\.\d km/ },
    ],
  },
  {
    id: 'tool-freq-wave',
    steps: [
      { action: 'fill', selector: '#freq-mhz', value: '145.5' },
      { action: 'expectText', selector: '#freq-wavelength', pattern: /2\.060.*m/ },
    ],
  },
  {
    id: 'tool-power',
    steps: [
      { action: 'fill', selector: '#power-watts', value: '10' },
      { action: 'expectText', selector: '#power-dbm', text: '40.00' },
      { action: 'fill', selector: '#power-dbm-input', value: '40' },
      { action: 'expectText', selector: '#power-watts-out', pattern: /10\.0000/ },
    ],
  },
  {
    id: 'tool-filter',
    steps: [
      { action: 'fill', selector: '#filter-freq', value: '145.5' },
      { action: 'fill', selector: '#filter-impedance', value: '50' },
      { action: 'expectText', selector: '#filter-l', pattern: /0\.055/ },
      { action: 'expectText', selector: '#filter-c', pattern: /21\.88/ },
    ],
  },
  {
    id: 'tool-balun',
    steps: [
      { action: 'fill', selector: '#balun-source', value: '50' },
      { action: 'fill', selector: '#balun-load', value: '200' },
      { action: 'expectText', selector: '#balun-ratio', pattern: /1:2\.00/ },
      { action: 'expectText', selector: '#balun-ratio', pattern: /4\.00:1/ },
    ],
  },
  {
    id: 'tool-swr',
    steps: [
      { action: 'fill', selector: '#swr-forward', value: '50' },
      { action: 'fill', selector: '#swr-reflected', value: '2' },
      { action: 'expectText', selector: '#swr-value', text: '1.50' },
      { action: 'expectText', selector: '#swr-return-loss', text: '13.98 dB' },
    ],
  },
  {
    id: 'tool-fspl',
    steps: [
      { action: 'fill', selector: '#fspl-distance', value: '10' },
      { action: 'fill', selector: '#fspl-frequency', value: '145.5' },
      { action: 'expectText', selector: '#fspl-loss', text: '95.70 dB' },
    ],
  },
  {
    id: 'tool-runtime',
    steps: [
      { action: 'fill', selector: '#runtime-capacity', value: '2200' },
      { action: 'fill', selector: '#runtime-current', value: '500' },
      { action: 'expectText', selector: '#runtime-hours', text: '4.40 h' },
    ],
  },
  {
    id: 'tool-coax',
    steps: [
      { action: 'fill', selector: '#coax-length', value: '20' },
      { action: 'fill', selector: '#coax-atten', value: '6.7' },
      { action: 'expectText', selector: '#coax-loss', text: '1.34 dB' },
    ],
  },
  {
    id: 'tool-bandwidth',
    steps: [
      { action: 'fill', selector: '#bandwidth-rate', value: '1200' },
      { action: 'fill', selector: '#bandwidth-rolloff', value: '0.35' },
      { action: 'expectText', selector: '#bandwidth-result', text: '1620.0 Hz' },
    ],
  },
  {
    id: 'tool-duty',
    steps: [
      { action: 'fill', selector: '#duty-tx', value: '30' },
      { action: 'fill', selector: '#duty-period', value: '120' },
      { action: 'expectText', selector: '#duty-result', text: '25.0 %' },
    ],
  },
  {
    id: 'tool-divider',
    steps: [
      { action: 'fill', selector: '#divider-vin', value: '13.8' },
      { action: 'fill', selector: '#divider-r1', value: '10000' },
      { action: 'fill', selector: '#divider-r2', value: '2200' },
      { action: 'expectText', selector: '#divider-vout', text: '2.489 V' },
    ],
  },
  {
    id: 'tool-resistor-combo',
    steps: [
      { action: 'fill', selector: '#resistor-target', value: '1000' },
      { action: 'fill', selector: '#resistor-max-count', value: '2' },
      { action: 'expectText', selector: '#resistor-best', pattern: /1000\.00/ },
      { action: 'expectText', selector: '#resistor-best', pattern: /0\.00%/ },
    ],
  },
  {
    id: 'tool-mismatch',
    steps: [
      { action: 'fill', selector: '#mismatch-swr', value: '1.5' },
      { action: 'expectText', selector: '#mismatch-gamma', text: '0.2000' },
      { action: 'expectText', selector: '#mismatch-loss', text: '0.177 dB' },
    ],
  },
  {
    id: 'tool-xl',
    steps: [
      { action: 'fill', selector: '#xl-freq', value: '145.5' },
      { action: 'fill', selector: '#xl-inductance', value: '2.2' },
      { action: 'expectText', selector: '#xl-result', pattern: /2\.011.*k/ },
    ],
  },
  {
    id: 'tool-xc',
    steps: [
      { action: 'fill', selector: '#xc-freq', value: '145.5' },
      { action: 'fill', selector: '#xc-capacitance', value: '100' },
      { action: 'expectText', selector: '#xc-result', pattern: /10\.94/ },
    ],
  },
  {
    id: 'tool-quarter-wave',
    steps: [
      { action: 'fill', selector: '#quarter-wave-frequency', value: '145.5' },
      { action: 'fill', selector: '#quarter-wave-vf', value: '0.95' },
      { action: 'expectText', selector: '#quarter-wave-length', pattern: /0\.47.*m/ },
    ],
  },
  {
    id: 'tool-erp',
    steps: [
      { action: 'fill', selector: '#erp-power', value: '10' },
      { action: 'fill', selector: '#erp-loss', value: '1.5' },
      { action: 'fill', selector: '#erp-gain', value: '3' },
      { action: 'expectText', selector: '#erp-result', text: '14.13 W' },
    ],
  },
  {
    id: 'tool-dipole',
    steps: [
      { action: 'fill', selector: '#dipole-frequency', value: '145.5' },
      { action: 'expectText', selector: '#dipole-length', pattern: /0\.98.*m/ },
    ],
  },
  {
    id: 'tool-solar',
    steps: [
      { action: 'fill', selector: '#solar-watts', value: '100' },
      { action: 'fill', selector: '#solar-hours', value: '4' },
      { action: 'expectText', selector: '#solar-energy', text: '400.0 Wh' },
    ],
  },
  {
    id: 'tool-battery-current',
    steps: [
      { action: 'fill', selector: '#battery-voltage', value: '13.8' },
      { action: 'fill', selector: '#battery-load', value: '50' },
      { action: 'expectText', selector: '#battery-current', text: '3.62 A' },
    ],
  },
  {
    id: 'tool-muf',
    steps: [
      { action: 'fill', selector: '#muf-fof2', value: '6' },
      { action: 'fill', selector: '#muf-angle', value: '30' },
      { action: 'expectText', selector: '#muf-result', text: '6.93 MHz' },
    ],
  },
  {
    id: 'tool-eirp',
    steps: [
      { action: 'fill', selector: '#eirp-erp', value: '10' },
      { action: 'expectText', selector: '#eirp-result', text: '16.40 W' },
    ],
  },
  {
    id: 'tool-skip',
    steps: [
      { action: 'fill', selector: '#skip-height', value: '300' },
      { action: 'fill', selector: '#skip-angle', value: '30' },
      { action: 'expectText', selector: '#skip-result', pattern: /346\.4.*km/ },
    ],
  },
  {
    id: 'tool-db-sum',
    steps: [
      { action: 'fill', selector: '#dbsum-a', value: '30' },
      { action: 'fill', selector: '#dbsum-b', value: '30' },
      { action: 'expectText', selector: '#dbsum-result', text: '33.01 dBm' },
    ],
  },
  {
    id: 'tool-dbuv',
    steps: [
      { action: 'fill', selector: '#dbuv-dbm', value: '-73' },
      { action: 'expectText', selector: '#dbuv-result', pattern: /34\.00/ },
    ],
  },
  {
    id: 'tool-gain-conv',
    steps: [
      { action: 'fill', selector: '#gain-dbd', value: '3' },
      { action: 'expectText', selector: '#gain-dbi', text: '5.15 dBi' },
    ],
  },
  {
    id: 'tool-dbw',
    steps: [
      { action: 'fill', selector: '#dbw-dbm', value: '40' },
      { action: 'expectValue', selector: '#dbw-dbw-input', text: '10.00' },
      { action: 'fill', selector: '#dbw-dbw-input', value: '3' },
      { action: 'expectText', selector: '#dbw-result', text: '33.00 dBm' },
    ],
  },
  {
    id: 'tool-ohm-law',
    steps: [
      { action: 'fill', selector: '#ohm-voltage', value: '12.50' },
      { action: 'fill', selector: '#ohm-current', value: '2.50' },
      { action: 'expectValue', selector: '#ohm-resistance', text: '5.00' },
      { action: 'fill', selector: '#ohm-resistance', value: '10.00' },
      { action: 'expectValue', selector: '#ohm-voltage', text: '25.00' },
    ],
  },
  {
    id: 'tool-link-budget',
    steps: [
      { action: 'fill', selector: '#lb-ptx', value: '30' },
      { action: 'fill', selector: '#lb-gtx', value: '6' },
      { action: 'fill', selector: '#lb-grx', value: '6' },
      { action: 'fill', selector: '#lb-loss', value: '110' },
      { action: 'expectText', selector: '#lb-prx', text: '-68.00 dBm' },
    ],
  },
  unitConversionSteps('tool-unit-converter'),
  unitConversionSteps('tool-unit-conversions'),
  simpleConverterScenario('tool-dbuv-sunit', '60', '9'),
  simpleConverterScenario('tool-sunit-dbuv', '9', '60'),
  simpleConverterScenario('tool-rps-rpm', '1.5', '90'),
  simpleConverterScenario('tool-rpm-rps', '60', '1'),
  simpleConverterScenario('tool-s-ms', '1.5', '1500'),
  simpleConverterScenario('tool-ms-s', '1500', '1.5'),
  simpleConverterScenario('tool-wh-j', '1.5', '5400'),
  simpleConverterScenario('tool-j-wh', '3600', '1'),
  simpleConverterScenario('tool-db-pa', '60', '0.02'),
  simpleConverterScenario('tool-pa-db', '0.02', '60'),
  simpleConverterScenario('tool-f-c', '68', '20'),
  simpleConverterScenario('tool-c-f', '20', '68'),
  simpleConverterScenario('tool-ft-m', '3', '0.9144'),
  simpleConverterScenario('tool-in-mm', '2', '50.8'),
  simpleConverterScenario('tool-mhz-ghz', '1500', '1.5'),
  simpleConverterScenario('tool-khz-mhz', '1500', '1.5'),
  simpleConverterScenario('tool-hz-khz', '1500', '1.5'),
  simpleConverterScenario('tool-w-kw', '1500', '1.5'),
  simpleConverterScenario('tool-kw-w', '1.5', '1500'),
  simpleConverterScenario('tool-vpk-vrms', '1.4142135623730951', '1'),
  simpleConverterScenario('tool-vrms-vpp', '1', '2.828427'),
  simpleConverterScenario('tool-vpp-vrms', '2.8284271247461903', '1'),
];

const openTool = async (page: Page, toolId: string) => {
  await page.goto(`?route=tools#${toolId}`);
  await expect(page.locator(`#${toolId}`)).toBeVisible();
};

const runStep = async (page: Page, step: ToolStep) => {
  const target = page.locator(step.selector);

  if (step.action === 'fill') {
    await target.fill(step.value);
    return;
  }

  if (step.action === 'select') {
    await target.selectOption(step.value);
    return;
  }

  if (step.action === 'expectVisible') {
    await expect(target).toBeVisible();
    return;
  }

  if (step.action === 'expectValue') {
    await expect(target).toHaveValue(step.text);
    return;
  }

  if (step.action === 'expectContains') {
    await expect(target).toContainText(step.text);
    return;
  }

  if ('pattern' in step) {
    await expect(target).toHaveText(step.pattern);
    return;
  }

  await expect(target).toHaveText(step.text);
};

test.describe('tools calculator coverage', () => {
  for (const scenario of scenarios) {
    test(`${scenario.id} works`, async ({ page }) => {
      await openTool(page, scenario.id);
      for (const step of scenario.steps) {
        await runStep(page, step);
      }
    });
  }
});
