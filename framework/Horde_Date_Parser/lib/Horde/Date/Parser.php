
alias p_orig p

def p(val)
  p_orig val
  puts
end

class Time
  def self.construct(year, month = 1, day = 1, hour = 0, minute = 0, second = 0)
    if second >= 60
      minute += second / 60
      second = second % 60
    end
    
    if minute >= 60
      hour += minute / 60
      minute = minute % 60
    end
    
    if hour >= 24
      day += hour / 24
      hour = hour % 24
    end
    
    # determine if there is a day overflow. this is complicated by our crappy calendar
    # system (non-constant number of days per month)
    day <= 56 || raise("day must be no more than 56 (makes month resolution easier)")
    if day > 28
      # no month ever has fewer than 28 days, so only do this if necessary
      leap_year = (year % 4 == 0) && !(year % 100 == 0)
      leap_year_month_days = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
      common_year_month_days = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
      days_this_month = leap_year ? leap_year_month_days[month - 1] : common_year_month_days[month - 1]
      if day > days_this_month
        month += day / days_this_month
        day = day % days_this_month
      end
    end
    
    if month > 12
      if month % 12 == 0
        year += (month - 12) / 12
        month = 12
      else
        year += month / 12
        month = month % 12
      end
    end
    
    Time.local(year, month, day, hour, minute, second)
  end
end
