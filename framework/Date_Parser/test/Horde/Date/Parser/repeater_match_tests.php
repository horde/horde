  def test_match
    token = Chronic::Token.new('saturday')
    repeater = Chronic::Repeater.scan_for_day_names(token)
    assert_equal Chronic::RepeaterDayName, repeater.class
    assert_equal :saturday, repeater.type

    token = Chronic::Token.new('sunday')
    repeater = Chronic::Repeater.scan_for_day_names(token)
    assert_equal Chronic::RepeaterDayName, repeater.class
    assert_equal :sunday, repeater.type
  end
