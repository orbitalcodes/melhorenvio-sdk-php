<?php

namespace MelhorEnvio\Resoucers;

use Carbon\Carbon;
use DateInterval;
use DateTime;

class WorkingDays
{

	public static function getWorkingDays($startDate, $days)
	{
		$newDate = new DateTime();
		$newDate->add(new DateInterval('P' . $days . 'D'));

		$start = Carbon::now()->setDate($startDate->format('Y'), $startDate->format('m'), $startDate->format('d'));
		$end = Carbon::now()->setDate($newDate->format('Y'), $newDate->format('m'), $newDate->format('d'));
		$year = $start->format('Y');
		//todo: Criar um verificador de feriados
		$holidays = [
            Carbon::create($year, 1, 01), //reveillon
            Carbon::create($year, 2, 02), // Navegantes
            Carbon::create($year, 4, 21), // Dia de Tiradentes
            Carbon::create($year, 5, 01), // Dia do Trabalho
            Carbon::create($year, 9, 20), // Revolução Farroupilha
            Carbon::create($year, 10, 12), // DIA DAS CRIANÇAS
            Carbon::create($year, 11, 02), // finados
            Carbon::create($year, 11, 15), // Proclamação da República
            Carbon::create($year, 12, 25), // Natal
        ];

		$workingDays = $start->diffInDaysFiltered(function (Carbon $date) use ($holidays) {
			if (!$date->isWeekday()) {
				return false;
			}
			
			foreach ($holidays as $holiday) {
				if ($date->isSameDay($holiday)) {
					return false;
				}
			}
			
			return true;
		}, $end);

		$totalDays = $days + ($days - $workingDays);

		return date('Y/m/d', strtotime("+$totalDays days"));
	}
}
