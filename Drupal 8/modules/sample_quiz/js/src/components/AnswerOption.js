import React from 'react';
import PropTypes from 'prop-types';

function AnswerOption(props) {
  return (
    <div className="image_btn"  >
      <div className="image_btn__inner">
        <a href="#" id={props.index} onClick={props.onAnswerSelected}>
          <div className="image_btn__cta">
            <div className="btn">
              <h3>
                {props.answerContent}
              </h3>
            </div>
          </div>
          <div className="image_btn__image">
            <img
              src={props.image.url}
              width={props.image.width}
              height={props.image.height}
              alt={props.image.alt}
              title={props.image.title}
              typeof="foaf:Image"/>
          </div>
        </a>
      </div>
    </div>
  );
}

AnswerOption.propTypes = {
  image: PropTypes.object.isRequired,
  onAnswerSelected: PropTypes.func.isRequired,
  index: PropTypes.number.isRequired,
  answerContent: PropTypes.string.isRequired,
  questionId: PropTypes.number.isRequired,
};

export default AnswerOption;
