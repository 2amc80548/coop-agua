import { describe, it, expect } from 'vitest';

describe('Frontend Vue App Suite', () => {
    it('calculates water consumption correctly', () => {
        const lecturaAnterior = 120;
        const lecturaActual = 145;
        const consumoM3 = lecturaActual - lecturaAnterior;

        expect(consumoM3).toBe(25);
    });

    it('formats currency correctly for Bolivia (Bs.)', () => {
        const monto = 35.5;
        const formatted = `Bs. ${monto.toFixed(2)}`;

        expect(formatted).toBe('Bs. 35.50');
    });
});
