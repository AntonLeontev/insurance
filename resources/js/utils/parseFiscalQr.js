const FISCAL_QR_KEYS = /(?:[&7])?(?<![A-Za-z])(t|s|fn|i|fp|n)=/gi;

const RU_LAYOUT_TO_EN = {
	й: 'q', ц: 'w', у: 'e', к: 'r', е: 't', н: 'y', г: 'u', ш: 'i', щ: 'o', з: 'p',
	ф: 'a', ы: 's', в: 'd', а: 'f', п: 'g', р: 'h', о: 'j', л: 'k', д: 'l',
	я: 'z', ч: 'x', с: 'c', м: 'v', и: 'b', т: 'n', ь: 'm',
};

function convertRussianLayout(value) {
	return value.replace(/[а-яё]/gi, (char) => {
		const mapped = RU_LAYOUT_TO_EN[char.toLowerCase()];

		if (!mapped) {
			return char;
		}

		return char === char.toLowerCase() ? mapped : mapped.toUpperCase();
	});
}

export function parseFiscalQr(raw) {
	const normalized = convertRussianLayout(String(raw ?? '').replace(/[\s\n\r]+/g, ''));

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
