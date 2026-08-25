export function buildFiscalSearchFilters(values = {}) {
	return [
		{ column: 'fn_number', value: values.fn_number },
		{ column: 'fiscal_document_number', value: values.fiscal_document_number },
		{ column: 'fiscal_document_attribute', value: values.fiscal_document_attribute },
	]
		.map(({ column, value }) => ({ column, value: String(value ?? '').trim() }))
		.filter(({ value }) => value !== '');
}
