import React from 'react';
import PropTypes from 'prop-types';

function Question(props) {
  return (
    <div className="quiz_slide__teaser">
      <h2>{props.question}</h2>
    </div>
  );
}

Question.propTypes = {
  question: PropTypes.string.isRequired
};

export default Question;
