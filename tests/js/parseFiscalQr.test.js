import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { parseFiscalQr } from '../../resources/js/utils/parseFiscalQr.js';

const expected = {
	fn_number: '73814409008203527',
	fiscal_document_number: '130367',
	fiscal_document_attribute: '5280082237',
};

describe('parseFiscalQr', () => {
	it('parses a compact scanner string without separators', () => {
		assert.deepEqual(
			parseFiscalQr('t=20260817t13327s=5220.897fn=73814409008203527i=130367fp=5280082237n=1'),
			expected,
		);
	});

	it('parses a canonical FNS string with ampersands', () => {
		assert.deepEqual(
			parseFiscalQr('t=20260817T133227&s=5220.89&fn=73814409008203527&i=130367&fp=5280082237&n=1'),
			expected,
		);
	});

	it('parses an OFD URL with query string', () => {
		assert.deepEqual(
			parseFiscalQr('https://consumer.1-ofd.ru/v1?t=20260817T133227&s=5220.89&fn=73814409008203527&i=130367&fp=5280082237&n=1'),
			expected,
		);
	});

	it('ignores whitespace and newlines in a compact string', () => {
		assert.deepEqual(
			parseFiscalQr('t=20260817t13327s=5220.897\nfn=73814409008203527 i=130367\r\nfp=5280082237n=1'),
			expected,
		);
	});

	it('returns null for empty, missing, or incomplete input', () => {
		assert.equal(parseFiscalQr(''), null);
		assert.equal(parseFiscalQr(null), null);
		assert.equal(parseFiscalQr(undefined), null);
		assert.equal(parseFiscalQr('not-a-fiscal-qr'), null);
		assert.equal(parseFiscalQr('t=20260817t13327s=5220.897n=1'), null);
		assert.equal(parseFiscalQr('fn=73814409008203527i=130367'), null);
	});

	it('does not treat n= inside fn= as a key boundary', () => {
		const parsed = parseFiscalQr('fn=73814409008203527i=130367fp=5280082237n=1');

		assert.equal(parsed.fn_number, '73814409008203527');
		assert.equal(parsed.fiscal_document_number, '130367');
		assert.equal(parsed.fiscal_document_attribute, '5280082237');
		assert.equal(Object.hasOwn(parsed, 'n'), false);
		assert.deepEqual(Object.keys(parsed), [
			'fn_number',
			'fiscal_document_number',
			'fiscal_document_attribute',
		]);
	});
});
