const FISCAL_QR_KEYS = /(?:[&7])?(?<![A-Za-z])(t|s|fn|i|fp|n)=/gi;

export function parseFiscalQr(raw) {
	const normalized = String(raw ?? '').replace(/[\s\n\r]+/g, '');

	if (!normalized) {
		return null;
	}

	let query = normalized;
	const questionIndex = normalized.indexOf('?');

	if (questionIndex !== -1) {
		query = normalized.slice(questionIndex + 1);
	}

	const params = {};
	FISCAL_QR_KEYS.lastIndex = 0;
	const matches = [...query.matchAll(FISCAL_QR_KEYS)];

	for (let index = 0; index < matches.length; index++) {
		const match = matches[index];
		const key = match[1].toLowerCase();
		const valueStart = match.index + match[0].length;
		const valueEnd = index + 1 < matches.length ? matches[index + 1].index : query.length;

		params[key] = query.slice(valueStart, valueEnd).replace(/^&+|&+$/g, '');
	}

	const fn = params.fn?.trim();
	const documentNumber = params.i?.trim();
	const documentAttribute = params.fp?.trim();

	if (!fn || !documentNumber || !documentAttribute) {
		return null;
	}

	return {
		fn_number: fn,
		fiscal_document_number: documentNumber,
		fiscal_document_attribute: documentAttribute,
	};
}
