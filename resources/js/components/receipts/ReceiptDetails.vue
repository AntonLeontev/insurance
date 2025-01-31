<template>
	<v-table density="compact">
		<tbody>
			<tr>
				<td>Фaмилия</td>
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
		</tbody>
  	</v-table>
</template>

<script setup>
	import { defineProps } from 'vue';

	const props = defineProps({
		receipt: { required: true, default: {} },
	})
</script>
