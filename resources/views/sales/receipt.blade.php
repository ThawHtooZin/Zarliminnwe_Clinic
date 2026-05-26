@extends('layouts.app')

@section('title', 'Receipt '.$sale->sale_number)
@section('page-title', 'Sales Receipt')

@section('content')
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            html,
            body {
                width: 80mm;
                margin: 0 !important;
                background: #fff !important;
                color: #000 !important;
            }

            body > aside,
            body > header,
            .print-hidden {
                display: none !important;
            }

            main {
                min-height: auto !important;
                padding: 0 !important;
            }

            main > div {
                padding: 0 !important;
            }

            .receipt-page {
                background: #fff !important;
                padding: 0 !important;
            }

            .thermal-receipt {
                width: 80mm !important;
                max-width: 80mm !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                color: #000 !important;
                padding: 4mm !important;
            }

            .thermal-receipt,
            .thermal-receipt * {
                background: #fff !important;
                color: #000 !important;
                border-color: #000 !important;
                box-shadow: none !important;
            }
        }
    </style>

    <div class="receipt-page min-h-[calc(100vh-7rem)] bg-[#f8f9fa]">
        <div class="print-hidden mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-[#191c1d]">Sales Receipt</h1>
                <p class="mt-1 text-sm text-[#3e494a]">{{ $sale->sale_number }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('sales.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Back to Sales</a>
                <button type="button" onclick="window.print()" class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Print Receipt</button>
            </div>
        </div>

        <article class="thermal-receipt mx-auto w-[500px] rounded-lg border border-[#bec8ca] bg-white p-8 shadow-sm">
            <header class="border-b border-[#bec8ca] pb-6 text-center">
                <img src="{{ asset('images/zlmnlogo.jpg') }}" alt="ZARLI MIN NWE Clinic logo" class="mx-auto h-20 w-20 rounded-xl object-cover">
                <h2 class="mt-4 text-xl font-bold text-[#00535b]">ZARLI MIN NWE SPECIALIST CLINIC</h2>
                <p class="mt-1 text-sm text-[#3e494a]">Yangon, Myanmar · Tel: +95 9 123 456 789</p>
            </header>

            <section class="grid grid-cols-2 gap-y-5 border-b border-[#bec8ca] py-6 text-sm">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Receipt No.</p>
                    <p class="mt-1 font-bold text-[#191c1d]">{{ $sale->sale_number }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Date & Time</p>
                    <p class="mt-1 text-[#191c1d]">{{ $sale->sold_at?->format('M d, Y | h:i A') ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Cashier</p>
                    <p class="mt-1 text-[#191c1d]">{{ $sale->cashier?->name ?: '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium uppercase tracking-[0.06em] text-[#3e494a]">Patient</p>
                    <p class="mt-1 text-[#191c1d]">{{ $sale->patient_visit_id ?: 'No patient' }}</p>
                </div>
            </section>

            <section class="py-6">
                <table class="w-full text-sm">
                    <thead class="bg-[#edeeef] text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="px-3 py-2 text-left">Product Description</th>
                            <th class="px-3 py-2 text-center">Qty</th>
                            <th class="px-3 py-2 text-right">Price</th>
                            <th class="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#bec8ca]">
                        @foreach ($sale->lines as $line)
                            <tr>
                                <td class="px-3 py-2">
                                    <p class="font-medium text-[#191c1d]">{{ $line->product->name }}</p>
                                    <p class="text-xs text-[#3e494a]">{{ $line->productUnit->name }}</p>
                                </td>
                                <td class="px-3 py-2 text-center">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format((float) $line->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="space-y-3 border-t border-[#bec8ca] pt-4 text-sm">
                <div class="flex justify-between"><span class="text-[#3e494a]">Subtotal</span><span>{{ number_format((float) $sale->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-[#3e494a]">Discount</span><span>{{ number_format((float) $sale->discount_total, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-[#3e494a]">Tax</span><span>{{ number_format((float) $sale->tax_total, 2) }}</span></div>
                <div class="flex justify-between rounded bg-[#00535b01] px-2 py-3 text-xl font-bold text-[#00535b]">
                    <span>Grand Total</span>
                    <span>{{ number_format((float) $sale->grand_total, 2) }}</span>
                </div>
                <div class="rounded border border-[#6f797a] p-4">
                    <div class="flex justify-between"><span class="text-[#3e494a]">Payment Method</span><span class="capitalize">{{ $sale->payment_method }}</span></div>
                    <div class="mt-2 flex justify-between"><span class="text-[#3e494a]">Amount Paid</span><span>{{ number_format((float) $sale->amount_paid, 2) }}</span></div>
                    <div class="mt-2 flex justify-between"><span class="text-[#3e494a]">Change</span><span>{{ number_format((float) $sale->change_amount, 2) }}</span></div>
                </div>
            </section>

            <footer class="mt-8 border-t border-[#bec8ca] pt-6 text-center">
                <div class="mx-auto mb-4 h-10 w-48 bg-[repeating-linear-gradient(90deg,#191c1d_0,#191c1d_2px,transparent_2px,transparent_5px)] opacity-60"></div>
                <p class="text-sm italic text-[#3e494a]">
                    Thank you for choosing ZARLI MIN NWE Specialist Clinic. We wish you a speedy recovery!
                </p>
            </footer>
        </article>
    </div>
@endsection
