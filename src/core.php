<?php
	/*
	* Copyright (C) 2014 Яницкий Вадим
	* https://github.com/axilirator/firewind
	*
    * FireWind is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.

    * FireWind is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.

    * You should have received a copy of the GNU General Public License
    * along with FireWind. If not, see <http://www.gnu.org/licenses/>.
    */

	namespace Firewind;
	use stdClass;

	class index {
		public $words = [];
		public $count = 0;
	}

	class index_node {
		public $count = 1;
		public $source;
		public $weight;
		public $basic;

		public function range() {
			return $this->weight * $this->count;
		}
	}

	class core {
		public  $VERSION = "1.0.0";
		private $morphyus;

		function __construct() {
			$this->morphyus = new morphyus;
		}

		/**
		 * Выполняет индексирование текста
		 *
		 * @param  {string}  content Текст для индексирования
		 * @param  {integer} [range] Коэффициент значимости индексируемых данных
		 * @return {object}          Результат индексирования
		 */
		public function make_index( $content, $range=1 ) {
			$index        = new index;
			$index->range = $range;

			// Выделение слов из текста //
			$words = $this->morphyus->get_words( $content );

			foreach ( $words as $word ) {
				// Оценка значимости слова //
				$weight = $this->morphyus->weigh( $word );

				if ( $weight > 0 ) {
					// Количество слов в индексе //
					$length = $index->count;

					// Проверка существования исходного слова в индексе //
					for ( $i = 0; $i < $length; $i++ ) {
						if ( $index->words[ $i ]->source === $word ) {
							// Исходное слово уже есть в индексе //
							$index->words[ $i ]->count++;
							$index->words[ $i ]->range = 
								$range * $index->words[ $i ]->count * $index->words[ $i ]->weight;

							// Обработка следующего слова //
							continue 2;
						}
					}

					// Если исходного слова еще нет в индексе //
					$lemma = $this->morphyus->lemmatize( $word );

					if ( $lemma ) {
						// Проверка наличия лемм в индексе //
						for ( $i = 0; $i < $length; $i++ ) {
							// Если у сравниваемого слова есть леммы //
							if ( $index->words[ $i ]->basic ) {
								$difference = count(
									array_diff( $lemma, $index->words[ $i ]->basic )
								);

								// Если леммы исходного слова совпали //
								if ( $difference === 0 ) {
									$index->words[ $i ]->count++;
									$index->words[ $i ]->range = 
										$range * $index->words[ $i ]->count * $index->words[ $i ]->weight;

									// Обработка следующего слова //
									continue 2;
								}
							}
						}
					}

					// Если в индексе нет ни лемм, ни исходного слова, //
					// значит пора добавить его //
					$node = new index_node;
					$node->source = $word;
					$node->weight = $weight;
					$node->basic  = $lemma;

					$index->words[] = $node;
					$index->count++;
				}
			}

			return $index;
		}

		/**
		 * Выполняет поиск ключевых слов одного индексного объекта в другом
		 *
		 * @param  {object} target Искомые данные
		 * @param  {object} source Данные, в которых выполняется поиск
		 * @return {number}        Суммарный ранг на основе найденных данных
		 */
		public function search( $target, $index ) {
			$total_range = 0;

			// Перебор ключевых слов запроса //
			foreach ( $target->words as $target_word ) {
				// Перебор ключевых слов индекса //
				foreach ( $index->words as $index_word ) {
					if ( $index_word->source === $target_word->source ) {
						$total_range += $index_word->range;
					} else if ( $index_word->basic && $target_word->basic ) {
						// Если у искомого и индексированного слов есть леммы //
						$index_count  = count( $index_word  ->basic );
						$target_count = count( $target_word ->basic );

						for ( $i = 0; $i < $target_count; $i++ ) {
							for ( $j = 0; $j < $index_count; $j++ ) {
								if ( $index_word->basic[ $j ] === $target_word->basic[ $i ] ) {
									$total_range += $index_word->range;
									continue 2;
								}
							}
						}
					}
				}
			}

			return $total_range;
		}
	}
?>