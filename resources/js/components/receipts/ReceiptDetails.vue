<template>
	<v-table density="compact" :style="{width: props.width}" v-if="props.receipt !== null">
		<tbody>
			<tr>
				<td width="45%">Фaмилия</td>
				<td>{{ props.receipt.surname }}</td>
			</tr>
			<tr>
				<td>Имя</td>
				<td>{{ props.receipt.name }}</td>
			</tr>
			<tr>
				<td>Отчество</td>
				<td>{{ props.receipt.patronymic }}</td>
			</tr>
			<tr>
				<td>Паспорт</td>
				<td>{{ props.receipt.passport }}</td>
			</tr>
			<tr>
				<td>Страховая компания</td>
				<td>{{ props.receipt.insurer_name }}</td>
			</tr>
			<tr>
				<td>ИНН страховой компании</td>
				<td>{{ props.receipt.insurer_inn }}</td>
			</tr>
			<tr>
				<td>Тип договора</td>
				<td>{{ props.receipt.contract_name }}</td>
			</tr>
			<tr>
				<td>Серия договора</td>
				<td>{{ props.receipt.contract_series }}</td>
			</tr>
			<tr>
				<td>Номер договора</td>
				<td>{{ props.receipt.contract_number }}</td>
			</tr>
			<tr>
				<td>Почта клиента</td>
				<td>{{ props.receipt.client_email }}</td>
			</tr>
			<tr>
				<td>Почта агента</td>
				<td>{{ props.receipt.agent_email }}</td>
			</tr>
			<tr>
				<td>Сумма</td>
				<td>{{ (+props.receipt.amount).toLocaleString('ru-RU') }}</td>
			</tr>
			<tr v-if="props.receipt.payment_type">
				<td>Тип оплаты</td>
				<td>{{ props.receipt.payment_type === 'cash' ? 'Наличный расчет' : 'Безналичный расчет' }}</td>
			</tr>
			<tr v-if="props.receipt.status">
				<td>Статус</td>
				<td>
					<span class="" v-if="props.receipt.status === 'wait'">
						<v-icon icon="mdi-timer-sand" />
						В обработке
					</span>
					<span class="" v-if="props.receipt.status === 'done'">
						<v-icon icon="mdi-check-circle-outline" color="primary" />
						Успешно
					</span>
					<span class="" v-if="props.receipt.status === 'fail'">
						<v-icon icon="mdi-alert" color="danger" />
						Ошибка
					</span>
				</td>
			</tr>
			<tr v-if="props.receipt.error_text">
				<td>Текст ошибки</td>
				<td>{{ props.receipt.error_text }}</td>
			</tr>
			<tr v-if="props.receipt.fiscal_receipt_number">
				<td>Номер чека в смене</td>
				<td>{{ props.receipt.fiscal_receipt_number }}</td>
			</tr>
			<tr v-if="props.receipt.shift_number">
				<td>Номер смены</td>
				<td>{{ props.receipt.shift_number }}</td>
			</tr>
			<tr v-if="props.receipt.receipt_datetime">
				<td>Дата и время</td>
				<td>{{ props.receipt.receipt_datetime }}</td>
			</tr>
			<tr v-if="props.receipt.fn_number">
				<td>Номер ФН</td>
				<td>{{ props.receipt.fn_number }}</td>
			</tr>
			<tr v-if="props.receipt.ecr_registration_number">
				<td>Регистрационный номер ККТ</td>
				<td>{{ props.receipt.ecr_registration_number }}</td>
			</tr>
			<tr v-if="props.receipt.fiscal_document_number">
				<td>Фискальный номер документа</td>
				<td>{{ props.receipt.fiscal_document_number }}</td>
			</tr>
			<tr v-if="props.receipt.fiscal_document_attribute">
				<td>Фискальный признак документа</td>
				<td>{{ props.receipt.fiscal_document_attribute }}</td>
			</tr>
			<tr v-if="props.receipt.ofd_receipt_url">
				<td>Ссылка на чек в ОФД</td>
				<td><a :href="props.receipt.ofd_receipt_url" target="_blank" class="underline">Перейти</a></td>
			</tr>
		</tbody>
  	</v-table>
</template>

<script setup>
	const props = defineProps({
		receipt: { required: true, default: {} },
		width: { required: false },
	})
</script>
