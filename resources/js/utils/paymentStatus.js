function asPayment(payment) {
	if (payment == null || typeof payment !== 'object') {
		return null;
	}

	return payment;
}

export function paymentStatusLabel(payment) {
	const current = asPayment(payment);

	if (!current) {
		return typeof payment === 'string' ? payment : '';
	}

	if (current.status === 'REFUNDED') {
		return 'Возврат';
	}

	if (current.paid_at) {
		return 'Оплачен';
	}

	return current.status == null ? '' : String(current.status);
}

export function paymentStatusIcon(payment) {
	const current = asPayment(payment);

	if (current?.status === 'REFUNDED') {
		return 'mdi-arrow-u-up-right-bold';
	}

	if (current?.paid_at) {
		return 'mdi-check-circle-outline';
	}

	return 'mdi-information-outline';
}

export function paymentStatusColor(payment) {
	const current = asPayment(payment);

	if (current?.status === 'REFUNDED') {
		return 'danger';
	}

	if (current?.paid_at) {
		return 'primary';
	}

	return undefined;
}
