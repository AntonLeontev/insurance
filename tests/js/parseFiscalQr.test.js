import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { parseFiscalQr } from '../../resources/js/utils/parseFiscalQr.js';

const compactExpected = {
	fn_number: '7381440900820352',
	fiscal_document_number: '13036',
	fiscal_document_attribute: '528008223',
};

const canonicalExpected = {
	fn_number: '73814409008203527',
	fiscal_document_number: '130367',
	fiscal_document_attribute: '5280082237',
};

describe('parseFiscalQr', () => {
	it('parses a compact scanner string treating 7 before the next key as a delimiter', () => {
		assert.deepEqual(
			parseFiscalQr('t=20260817t13327s=5220.897fn=73814409008203527i=130367fp=5280082237n=1'),
			compactExpected,
		);
	});

	it('parses a canonical FNS string with ampersands without stripping trailing 7s', () => {
		assert.deepEqual(
			parseFiscalQr('t=20260817T133227&s=5220.89&fn=73814409008203527&i=130367&fp=5280082237&n=1'),
			canonicalExpected,
		);
	});

	it('parses an OFD URL with query string', () => {
		assert.deepEqual(
			parseFiscalQr('https://consumer.1-ofd.ru/v1?t=20260817T133227&s=5220.89&fn=73814409008203527&i=130367&fp=5280082237&n=1'),
			canonicalExpected,
		);
	});

	it('parses a scanner string typed on a Russian keyboard layout', () => {
		assert.deepEqual(
			parseFiscalQr('е=20260821е10467ы=507ат=73814409008203527ш=132727аз=30227837367т=1'),
			{
				fn_number: '7381440900820352',
				fiscal_document_number: '13272',
				fiscal_document_attribute: '3022783736',
			},
		);
	});

	it("parses a scanner string typed on a Russian keyboard layout with question marks", () => {
        assert.deepEqual(
            parseFiscalQr(
                "е=20260821е1046?ы=50?ат=7381440900820352?ш=13272?аз=3022783736?т=1",
            ),
            {
                fn_number: "7381440900820352",
                fiscal_document_number: "13272",
                fiscal_document_attribute: "3022783736",
            },
        );
    });

	it('parses Russian-layout keys mixed with Latin separators', () => {
		assert.deepEqual(
			parseFiscalQr('Е=20260821Е1046&Ы=50&АТ=7381440900820352&Ш=13272&АЗ=3022783736&Т=1'),
			{
				fn_number: '7381440900820352',
				fiscal_document_number: '13272',
				fiscal_document_attribute: '3022783736',
			},
		);
	});

	it('ignores whitespace and newlines in a compact string', () => {
		assert.deepEqual(
			parseFiscalQr('t=20260817t13327s=5220.897\nfn=73814409008203527 i=130367\r\nfp=5280082237n=1'),
			compactExpected,
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

		assert.equal(parsed.fn_number, '7381440900820352');
		assert.equal(parsed.fiscal_document_number, '13036');
		assert.equal(parsed.fiscal_document_attribute, '528008223');
		assert.equal(Object.hasOwn(parsed, 'n'), false);
		assert.deepEqual(Object.keys(parsed), [
			'fn_number',
			'fiscal_document_number',
			'fiscal_document_attribute',
		]);
	});
});
