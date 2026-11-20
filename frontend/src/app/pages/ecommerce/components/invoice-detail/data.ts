import { InvoiceData } from './types';

export const defaultInvoiceData: InvoiceData = {
  invoiceId: 'TC-2026-0001',
  from: {
    label: 'De',
    name: 'TalitaCumi',
    addressLines: ['Centro Integral de Desarrollo Infantil', 'Yacuiba, Tarija - Bolivia'],
    dateLabel: 'Emitido el:',
    date: '19 de noviembre de 2026',
  },
  to: {
    label: 'Para',
    name: 'María López',
    addressLines: ['Sala Inicial', 'Turno mensual - Noviembre 2026'],
    dateLabel: 'Vence el:',
    date: '30 de noviembre de 2026',
  },
  items: [
    { id: 1, product: 'Mensualidad Sala Inicial', quantity: 1, unitCost: 850, discount: 0, total: 850 },
    { id: 2, product: 'Material didáctico', quantity: 1, unitCost: 120, discount: 0, total: 120 },
    { id: 3, product: 'Taller de estimulación', quantity: 2, unitCost: 90, discount: 0, total: 180 },
    { id: 4, product: 'Seguro de accidentes', quantity: 1, unitCost: 50, discount: 0, total: 50 },
  ],
  summary: {
    subtotal: 1200,
    vatRate: 13,
    vatAmount: 156,
    total: 1356,
  },
};
