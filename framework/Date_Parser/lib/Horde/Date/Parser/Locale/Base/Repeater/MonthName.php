<?php
class Horde_Date_Parser_Locale_Base_Repeater_MonthName extends Horde_Date_Parser_Locale_Base_Repeater
{
    /**
     * 30 * 24 * 60 * 60
     */
    const MONTH_SECONDS = 2592000;

    public $currentMonthStart;

    public function next($pointer)
    {
        parent::next($pointer);

        if (!$this->currentMonthStart) {
            $targetMonth = $this->_monthNumber($this->type);
      case pointer
      when :future
        if @now.month < target_month
          @currentMonthStart = Time.construct(@now.year, target_month)
        else @now.month > target_month
          @currentMonthStart = Time.construct(@now.year + 1, target_month)
        end
      when :none
        if @now.month <= target_month
          @currentMonthStart = Time.construct(@now.year, target_month)
        else @now.month > target_month
          @currentMonthStart = Time.construct(@now.year + 1, target_month)
        end
      when :past
        if @now.month > target_month
          @currentMonthStart = Time.construct(@now.year, target_month)
        else @now.month < target_month
          @currentMonthStart = Time.construct(@now.year - 1, target_month)
        end
      end
      @currentMonthStart || raise("Current month should be set by now")
    else
      case pointer
      when :future
        @currentMonthStart = Time.construct(@currentMonthStart.year + 1, @currentMonthStart.month)
      when :past
        @currentMonthStart = Time.construct(@currentMonthStart.year - 1, @currentMonthStart.month)
      end
    end

    cur_month_year = @currentMonthStart.year
    cur_month_month = @currentMonthStart.month

    if cur_month_month == 12
      next_month_year = cur_month_year + 1
      next_month_month = 1
    else
      next_month_year = cur_month_year
      next_month_month = cur_month_month + 1
    end

    Chronic::Span.new(@currentMonthStart, Time.construct(next_month_year, next_month_month))
  end

    public funcction this($pointer = 'future')
    {
        parent::this($pointer);

        switch ($pointer) {
        case 'past':
            return $this->next($pointer);

        case 'future':
        case 'none':
            return $this->next('none');
        }
    }

    public function width()
    {
        return self::MONTH_SECONDS;
    }

    public function index()
    {
        return $this->_monthNumber($this->type);
    }

    public function __toString()
    {
        return parent::__toString() . '-monthname-' . $this->type;
    }

    protected function _monthNumber($monthName)
    {
    lookup = {:january => 1,
              :february => 2,
              :march => 3,
              :april => 4,
              :may => 5,
              :june => 6,
              :july => 7,
              :august => 8,
              :september => 9,
              :october => 10,
              :november => 11,
              :december => 12}
    lookup[sym] || raise("Invalid symbol specified")

}