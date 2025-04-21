@use(App\Enums\ReceiptType)

@extends('pdf.layout')

@push('styles')
	<style>
		.container {
			max-width: 450px;
			margin: 0 auto;
			font-size: 0.785rem;
		}	
		.qr-wrap {
			max-width: 150px;
			margin: 0 auto;
		}
		.blue {color: #004475}
		.center {text-align: center}
		.uppercase {text-transform: uppercase}
		.bold {font-weight: bold}
	</style>	
@endpush

@section('content')
	<div class="container">
		<div class="uppercase blue center bold">КАССОВЫЙ ЧЕК / {{ $receipt->receipt_type === ReceiptType::SELL ? 'ПРИХОД' : 'ВОЗВРАТ ПРИХОДА' }}</div>
		<br>
		<br>
		<div class="uppercase blue center bold">{{ $agency->name }}</div>
		<br>
		<table width="100%">
			<tbody>
				<tr>
					<td>ИНН {{ $agency->inn }}</td>
					<td align="right">Смена № {{ $receipt->shift_number }}</td>
				</tr>
				<tr>
					<td>Дата {{ $receipt->receipt_datetime }}</td>
					<td align="right">Чек № {{ $receipt->fiscal_receipt_number }}</td>
				</tr>
			</tbody>
		</table>

		<br>
		<br>

		<div class="blue bold">
			Договор страхования {{ $receipt->contract_series }} {{ $receipt->contract_number }}, вид страхования – {{ $receipt->contract_name }}
		</div>

		<br>

		<table width="100%">
			<tbody>
				<tr>
					<td>Цена с учетом скидок и наценок, всего</td>
					<td align="right" width="30%">{{ number_format($receipt->amount, 2, ',', ' ') }}&nbsp;₽</td>
				</tr>
				<tr>
					<td>Количество</td>
					<td align="right">1 ПОЛИС</td>
				</tr>
				<tr>
					<td>Цена за единицу товара с учетом скидок и наценок</td>
					<td align="right">{{ number_format($receipt->amount, 2, ',', ' ') }}&nbsp;₽</td>
				</tr>
				<tr>
					<td>Ставка НДС</td>
					<td align="right">{{ $receipt->vat->toString() }}</td>
				</tr>
				<tr>
					<td>ППР</td>
					<td align="right">УСЛУГА</td>
				</tr>
				<tr>
					<td>Признак агента по ПР</td>
					<td align="right">АГЕНТ</td>
				</tr>
			</tbody>
		</table>

		<br>

		<div class="blue bold">Данные поставщика</div>

		<table width="100%">
			<tbody>
				<tr>
					<td>Наименование поставщика</td>
					<td align="right">{{ $receipt->insurer_name }}</td>
				</tr>
				<tr>
					<td>ИНН поставщика</td>
					<td align="right">{{ $receipt->insurer_inn }}</td>
				</tr>
			</tbody>
		</table>

		<br>

		<table width="100%">
			<tbody>
				<tr class="blue bold" style="font-size: 1.2rem">
					<td>Итог</td>
					<td align="right">{{ number_format($receipt->amount, 2, ',', ' ') }}&nbsp;₽</td>
				</tr>
				<tr class="" style="">
					<td>{{ $receipt->payment_type->value === 'cash' ? 'НАЛИЧНЫМИ' : 'БЕЗНАЛИЧНЫЙ РАСЧЕТ' }}</td>
					<td align="right">{{ number_format($receipt->amount, 2, ',', ' ') }}&nbsp;₽</td>
				</tr>
			</tbody>
		</table>

		<br>

		<div class="blue bold">Данные места расчетов</div>
		<table width="100%">
			<tbody>
				<tr>
					<td>Место расчетов</td>
					<td align="right">{{ $agency->payment_address }}</td>
				</tr>
			</tbody>
		</table>

		<br>
		
		<div class="blue bold">Данные ККТ и чека</div>
		<table width="100%">
			<tbody>
				{{-- <tr>
					<td>РН ККТ</td>
					<td align="right"></td>
				</tr> --}}
				<tr>
					<td>ФН</td>
					<td align="right">{{ $receipt->fn_number }}</td>
				</tr>
				<tr>
					<td>ФД</td>
					<td align="right">{{ $receipt->fiscal_document_number }}</td>
				</tr>
				<tr>
					<td>ФПД</td>
					<td align="right">{{ $receipt->fiscal_document_attribute }}</td>
				</tr>
				<tr>
					<td>Версия ФФД</td>
					<td align="right">{{ $agency->ffd->toString() }}</td>
				</tr>
				<tr>
					<td>СНО</td>
					<td align="right">{{ $agency->sno->toString() }}</td>
				</tr>
				<tr>
					<td>Сайт ФНС</td>
					<td align="right">nalog.gov.ru</td>
				</tr>
			</tbody>
		</table>

		<br>

		<div class="blue bold">Данные о покупателе</div>
		<table width="100%">
			<tbody>
				<tr>
					<td>Покупатель</td>
					<td align="right">{{ sprintf('%s %s %s %s', $receipt->surname, $receipt->name, $receipt->patronymic, $receipt->passport) }}</td>
				</tr>
				<tr>
					<td>Эл. адрес покупателя</td>
					<td align="right">{{ $receipt->client_email }}</td>
				</tr>
				<tr>
					<td>Эл. адрес отправителя</td>
					<td align="right">{{ $agency->email }}</td>
				</tr>
			</tbody>
		</table>

		<br>

		<div class="">
			<div class="qr-wrap">
				<img src='data:image/svg+xml;base64,{{ $qrcode }}' width='100%' />
			</div>
		</div>
	</div>
@endsection

