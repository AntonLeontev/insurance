import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { buildFiscalSearchFilters } from '../../resources/js/utils/buildFiscalSearchFilters.js';

describe('buildFiscalSearchFilters', () => {
	it('returns only filled fields', () => {
		assert.deepEqual(
			buildFiscalSearchFilters({ fn_number: '1111222233334444' }),
			[{ column: 'fn_number', value: '1111222233334444' }],
		);

		assert.deepEqual(
			buildFiscalSearchFilters({
				fiscal_document_number: '555',
				fiscal_document_attribute: '6667778889',
			}),
			[
				{ column: 'fiscal_document_number', value: '555' },
				{ column: 'fiscal_document_attribute', value: '6667778889' },
			],
		);
	});

	it('keeps all three fields when they are filled', () => {
		assert.deepEqual(
			buildFiscalSearchFilters({
				fn_number: '1111222233334444',
				fiscal_document_number: '555',
				fiscal_document_attribute: '6667778889',
			}),
			[
				{ column: 'fn_number', value: '1111222233334444' },
				{ column: 'fiscal_document_number', value: '555' },
				{ column: 'fiscal_document_attribute', value: '6667778889' },
			],
		);
	});

	it('ignores empty, whitespace, and missing values', () => {
		assert.deepEqual(buildFiscalSearchFilters({}), []);
		assert.deepEqual(
			buildFiscalSearchFilters({
				fn_number: '  ',
				fiscal_document_number: null,
				fiscal_document_attribute: undefined,
			}),
			[],
		);
		assert.deepEqual(
			buildFiscalSearchFilters({
				fn_number: ' 1111 ',
				fiscal_document_number: '',
			}),
			[{ column: 'fn_number', value: '1111' }],
		);
	});
});
