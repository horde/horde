<?php
class Horde_Date_Parser_Locale_Base_Repeater_Second extends Horde_Date_Parser_Locale_Base_Repeater
{
    const SECOND_SECONDS = 1;

  def next(pointer = :future)
    super

    direction = pointer == :future ? 1 : -1

    if !@second_start
      @second_start = @now + (direction * SECOND_SECONDS)
    else
      @second_start += SECOND_SECONDS * direction
    end

    Chronic::Span.new(@second_start, @second_start + SECOND_SECONDS)
  end

  def this(pointer = :future)
    super

    Chronic::Span.new(@now, @now + 1)
  end

  def offset(span, amount, pointer)
    direction = pointer == :future ? 1 : -1
    span + direction * amount * SECOND_SECONDS
  end

  def width
    SECOND_SECONDS
  end

  def to_s
    super << '-second'
  end
end
